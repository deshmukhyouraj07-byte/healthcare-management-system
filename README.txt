HOW TO ADD A DOCTOR'S PHOTO
============================

1. Find the doctor's USERNAME (their login ID — the same one used to log
   into portal.php, e.g. "dr_yuvraj"). You can see it on their Virtual ID
   Card, or in phpMyAdmin under users.username.

2. Name your image file EXACTLY that username, with one of these
   extensions: .jpg  .jpeg  .png  .webp

   Example: if the doctor's username is "dr_yuvraj", the file should be:
     dr_yuvraj.jpg

3. Drop that file directly into this folder:
     /assets/doctors/

That's it — no code changes needed. doctor_detail.php automatically looks
for a matching file here (trying .jpg, .jpeg, .png, .webp in that order)
and shows it on that doctor's profile page. If no matching file is found,
a placeholder icon is shown instead — nothing breaks either way.

Recommended: roughly square photos (e.g. 500x500px) look best, since
they're displayed inside a circular frame.
