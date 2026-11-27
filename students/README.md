# Student Tracker

Collects student details and follows each student's education and performance for 3 to 5 years,
so the best candidates can be found for courses, scholarships and jobs.

## Use it
1. Open `/students` on the site. The first visit asks for the organisation name and the admin password.
   Open it yourself straight after deploying, so nobody else sets the password first.
2. Add students yourself (All students → Add student), or share the sign-up link `/students/register.php`.
   Sign-ups arrive under **New sign-ups**; check them and press **Approve**.
3. On a student's page, add a performance record each term / semester / year: marks %, attendance %,
   skills (0–10), rank, achievements, remarks.
4. **Best candidates** ranks active students by score; filter by course and minimum score. Shortlist the ones you pick.
5. **Download spreadsheet** (Overview) gives every student with their latest record as a CSV for Excel.

## Score
Each record: marks, attendance and skills (×10) averaged with the weights in Settings (default 60 / 20 / 20).
An empty part is skipped. Overall score: the latest three records, weighted 1, 2, 3 (newest counts most).
Trend: latest record score minus the first one.

## Consent and privacy
- The public form needs a consent tick; for a student under 18 a parent / guardian name and their consent are required.
- Consent date and who gave it are saved with each student. Students added by hand should have it filled in (Edit → Consent).
- "Delete student" removes the student and every record.
- Only the admin password opens the data. Data is not shown on any public page.

## Files
- `index.php`: admin dashboard · `register.php`: public sign-up form · `api.php`: all actions · `lib.php`: helpers and scoring
- Data is saved in `students-data/` next to the website folder (one level above `public_html`), outside what a deploy replaces.
  `data/` inside this folder is only the fallback, blocked from the web and not in git. Back up `students-data/` regularly.

## Notes
- Forgot the password: delete `settings.json` in `students-data` (File Manager) and set up again; students are kept.
- Clean URLs: `someswara.com/students/welcome` (welcome page: goal, motto, the journey, growth chart) and `someswara.com/students/register` (sign-up form).
- The motto and goal on the welcome page are edited in Settings → Welcome page.
