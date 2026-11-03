<?php
// Shared helpers: settings, login, RunPod API, ComfyUI API, video workflow.

const DATA_DIR = __DIR__ . '/data';
const CLIPS_DIR = DATA_DIR . '/clips';
const CLIENT_ID = 'previs-panel';

// GPU preference order (first available is used)
const GPU_TYPES = ['NVIDIA GeForce RTX 4090', 'NVIDIA GeForce RTX 5090', 'NVIDIA L40S', 'NVIDIA A100 80GB PCIe'];
const POD_IMAGE = 'runpod/pytorch:2.8.0-py3.11-cuda12.8.1-cudnn-devel-ubuntu22.04';

const HF = 'https://huggingface.co/Comfy-Org/Wan_2.1_ComfyUI_repackaged/resolve/main/split_files';
const UNET = 'wan2.1_i2v_480p_14B_fp8_e4m3fn.safetensors';
const TEXT_ENCODER = 'umt5_xxl_fp8_e4m3fn_scaled.safetensors';
const VAE = 'wan_2.1_vae.safetensors';
const CLIP_VISION = 'clip_vision_h.safetensors';

if (!is_dir(CLIPS_DIR)) @mkdir(CLIPS_DIR, 0755, true);

function read_json(string $file, $default = []) {
    $p = DATA_DIR . "/$file";
    if (!is_file($p)) return $default;
    $d = json_decode((string)file_get_contents($p), true);
    return is_array($d) ? $d : $default;
}

function write_json(string $file, $data): void {
    $p = DATA_DIR . "/$file";
    file_put_contents("$p.tmp", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    rename("$p.tmp", $p);
}

function settings(): array { return read_json('settings.json'); }
function is_setup(): bool { return !empty(settings()['password_hash']); }
function logged_in(): bool { return !empty($_SESSION['ok']); }

// ---------- HTTP ----------
function http(string $method, string $url, array $opt = []): array {
    $ch = curl_init($url);
    $headers = $opt['headers'] ?? [];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $opt['timeout'] ?? 30,
    ]);
    if (isset($opt['json'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($opt['json'], JSON_UNESCAPED_SLASHES));
        $headers[] = 'Content-Type: application/json';
    } elseif (isset($opt['form'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opt['form']);
    }
    if (isset($opt['file'])) {
        $fh = fopen($opt['file'], 'wb');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // must come before CURLOPT_FILE
        curl_setopt($ch, CURLOPT_FILE, $fh);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if (isset($fh)) fclose($fh);
    return ['code' => $code, 'body' => is_string($body) ? $body : '', 'err' => $err];
}

// ---------- RunPod ----------
function rp(string $method, string $path, ?array $json = null): array {
    $key = settings()['api_key'] ?? '';
    $opt = ['headers' => ["Authorization: Bearer $key"]];
    if ($json !== null) $opt['json'] = $json;
    $base = getenv('PREVIS_RUNPOD_BASE') ?: 'https://rest.runpod.io/v1';
    $r = http($method, $base . $path, $opt);
    $r['data'] = json_decode($r['body'], true);
    return $r;
}

function rp_error(array $r): string {
    $d = $r['data'];
    $msg = is_array($d) ? ($d['error'] ?? $d['message'] ?? '') : '';
    if (is_array($msg)) $msg = json_encode($msg);
    if (!$msg) $msg = $r['err'] ?: trim(substr($r['body'], 0, 200));
    if ($r['code'] === 401) return 'RunPod rejected the API key. Check it in Settings.';
    return $msg ?: "RunPod error (HTTP {$r['code']})";
}

function pod_env(array $s): array {
    $models = implode("\n", [
        HF . '/diffusion_models/' . UNET . ' diffusion_models',
        HF . '/text_encoders/' . TEXT_ENCODER . ' text_encoders',
        HF . '/vae/' . VAE . ' vae',
        HF . '/clip_vision/' . CLIP_VISION . ' clip_vision',
    ]);
    $env = [
        'MODELS' => $models,
        'GUARDIAN_API_KEY' => $s['api_key'],
        'IDLE_MINUTES' => (string)($s['idle_minutes'] ?? 15),
        'GUARDIAN_B64' => base64_encode(file_get_contents(__DIR__ . '/pod/guardian.py')),
        'PROGRESS_B64' => base64_encode(file_get_contents(__DIR__ . '/pod/progress.py')),
        'BOOT_B64' => base64_encode(str_replace("\r", '', file_get_contents(__DIR__ . '/pod/bootstrap.sh'))),
    ];
    if (!empty($s['volume_id'])) $env['BASE_DIR'] = '/workspace';
    return $env;
}

function pod_create(): array {
    $s = settings();
    $body = [
        'name' => 'previs-engine',
        'imageName' => POD_IMAGE,
        'gpuTypeIds' => GPU_TYPES,
        'gpuCount' => 1,
        'allowedCudaVersions' => ['12.8', '12.9', '13.0'], // the image needs CUDA 12.8+ drivers
        'cloudType' => 'SECURE',
        'ports' => ['8188/http', '8189/http'],
        'env' => pod_env($s),
        'dockerEntrypoint' => ['bash', '-c'],
        'dockerStartCmd' => ['echo $BOOT_B64 | base64 -d | bash'],
    ];
    if (!empty($s['volume_id'])) {
        $body['networkVolumeId'] = $s['volume_id'];
        $body['volumeMountPath'] = '/workspace';
        $body['containerDiskInGb'] = 30;
    } else {
        $body['containerDiskInGb'] = 80;
    }
    return rp('POST', '/pods', $body);
}

function pod_url(string $id, int $port): string {
    if ($mock = getenv('PREVIS_POD_BASE')) return "$mock/$port"; // local testing only
    return "https://$id-$port.proxy.runpod.net";
}

// ---------- ComfyUI ----------
function comfy(string $method, string $path, array $opt = []): array {
    $pod = read_json('pod.json');
    if (empty($pod['id'])) return ['code' => 0, 'body' => '', 'err' => 'GPU is off'];
    return http($method, pod_url($pod['id'], 8188) . $path, $opt);
}

function boot_status(string $id): string {
    $r = http('GET', pod_url($id, 8189) . '/status.txt', ['timeout' => 8]);
    return $r['code'] === 200 ? trim($r['body']) : '';
}

function render_progress(string $id): array {
    $r = http('GET', pod_url($id, 8189) . '/progress.json', ['timeout' => 8]);
    $d = json_decode($r['body'], true);
    return is_array($d) ? $d : [];
}

function build_workflow(string $prompt, string $image, int $frames, int $steps, string $prefix): array {
    $neg = 'blurry, low quality, distorted face, extra limbs, deformed hands, static frame, watermark, subtitles, jpeg artifacts';
    return [
        '1' => ['class_type' => 'UNETLoader', 'inputs' => ['unet_name' => UNET, 'weight_dtype' => 'default']],
        '2' => ['class_type' => 'CLIPLoader', 'inputs' => ['clip_name' => TEXT_ENCODER, 'type' => 'wan']],
        '3' => ['class_type' => 'VAELoader', 'inputs' => ['vae_name' => VAE]],
        '4' => ['class_type' => 'CLIPVisionLoader', 'inputs' => ['clip_name' => CLIP_VISION]],
        '5' => ['class_type' => 'LoadImage', 'inputs' => ['image' => $image]],
        '6' => ['class_type' => 'CLIPVisionEncode', 'inputs' => ['clip_vision' => ['4', 0], 'image' => ['5', 0], 'crop' => 'none']],
        '7' => ['class_type' => 'CLIPTextEncode', 'inputs' => ['text' => $prompt, 'clip' => ['2', 0]]],
        '8' => ['class_type' => 'CLIPTextEncode', 'inputs' => ['text' => $neg, 'clip' => ['2', 0]]],
        '9' => ['class_type' => 'WanImageToVideo', 'inputs' => [
            'positive' => ['7', 0], 'negative' => ['8', 0], 'vae' => ['3', 0],
            'clip_vision_output' => ['6', 0], 'start_image' => ['5', 0],
            'width' => 832, 'height' => 480, 'length' => $frames, 'batch_size' => 1]],
        '10' => ['class_type' => 'ModelSamplingSD3', 'inputs' => ['model' => ['1', 0], 'shift' => 8.0]],
        '11' => ['class_type' => 'KSampler', 'inputs' => [
            'model' => ['10', 0], 'positive' => ['9', 0], 'negative' => ['9', 1], 'latent_image' => ['9', 2],
            'seed' => random_int(0, 2 ** 48), 'steps' => $steps, 'cfg' => 6.0,
            'sampler_name' => 'uni_pc', 'scheduler' => 'simple', 'denoise' => 1.0]],
        '12' => ['class_type' => 'VAEDecode', 'inputs' => ['samples' => ['11', 0], 'vae' => ['3', 0]]],
        '13' => ['class_type' => 'CreateVideo', 'inputs' => ['images' => ['12', 0], 'fps' => 16]],
        '14' => ['class_type' => 'SaveVideo', 'inputs' => [
            'video' => ['13', 0], 'filename_prefix' => $prefix, 'format' => 'mp4', 'codec' => 'h264']],
    ];
}
