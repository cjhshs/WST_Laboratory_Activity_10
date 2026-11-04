# Reflection Questions

1. Why should passwords be hashed before being saved to MySQL?
A hash is a one-way transformation, so even someone who steals the database
cannot read the original passwords. `password_hash()` also adds a random salt,
which stops attackers from reusing precomputed rainbow tables, and a deliberately
slow algorithm makes guessing expensive. The application only needs to compare
hashes with `password_verify()`, never recover the password.

2. What is the difference between authentication and authorization?
Authentication proves who a user is, for example by checking an email and
password at login. Authorization decides what that already-identified user is
allowed to do, such as opening the administrator dashboard. Authentication must
happen first; a valid student session is still not permission to view admin data.

3. How does a PHP session help protect a dashboard?
After a successful login the server stores `user_id`, `email` and `role` in a
session file and gives the browser only a random session ID cookie. Each
protected page checks that session before rendering anything, so a visitor
without a valid session is redirected to the login page. Regenerating the ID
after login also prevents an attacker from fixing a known session ID in advance.

3. Why should an application verify a user's role on the server?
Anything sent to the browser can be changed: links can be typed directly, forms
can be edited, and requests can be replayed with tools. If the role check only
hides a link, a student can still request `admin.php` and see the data. Checking
the role in PHP before producing output makes the server the single authority,
and returning HTTP 403 clearly refuses the request.

5. How do prepared statements protect SQL queries?
A prepared statement sends the SQL template and the values separately, so the
database treats bound values as data and never as SQL syntax. Even input such as
`' OR 1=1 --` becomes an ordinary string instead of changing the query. This is
why the login lookup, registration insert and comment insert all use
placeholders and `bind_param()`.

6. What is SQL injection, and how could it affect a student information system?
SQL injection happens when untrusted input is concatenated into a query and the
database executes it as code. In a student information system an attacker could
bypass the login, read or alter grades and personal data, or delete records.
Prepared statements and input validation prevent that by keeping data and
instructions separate.

7. What is XSS, and why can it affect other users?
Cross-site scripting is when an attacker's HTML or JavaScript is stored or
reflected by an application and then run in another user's browser. A malicious
comment on a shared board executes for every student who opens the page, which
can steal session cookies, change what the page shows, or perform actions as the
victim. Stored XSS is especially dangerous because the payload keeps working
until it is removed.

8. How does `htmlspecialchars()` help protect displayed comments?
It converts characters with special meaning in HTML, such as `<`, `>`, `&` and
quotes, into entities like `&lt;` and `&#039;`. The browser then shows the text
instead of treating it as markup, so a comment containing a script tag appears
as literal text. Using `ENT_QUOTES` also protects attribute contexts, and
`ENT_SUBSTITUTE` with UTF-8 avoids returning an empty string for invalid bytes.

9. Why is it necessary to test direct access to protected URLs?
Hiding a link does not protect a page, and a user can open any URL directly or
send a request with a tool. Testing direct access proves that the server-side
session and role checks actually run before output. It also catches forgotten
guards, such as a page that works only because the navigation does not link to it.

10. Which security control was the most challenging to implement, and why?
The authorization step was the hardest to get right while testing. A session
stores the role at login time, so changing a user's role in the database does not
affect an existing session until the user logs out and back in. That behaviour is
correct for the activity but easy to misread as a failed role check, and it
reminded me that role changes may need to force re-authentication in a real
system.
