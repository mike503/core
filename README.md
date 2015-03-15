# Why?

There's plenty of OO frameworks out there, however I am a purely procedural guy. I dislike namespaces, objects, and most of the newer PHP constructs. I think you can still make amazingly elegant things without them. Drupal 7 and previous versions are an example of an elegant procedurally-driven hook system.

There are so many different ways to do things. I like to create mature single purpose functions that provide the best experience with the best performance possible. I have been using bits and pieces of this "library" or "framework" for years, maturing them over time.

I am a heavy proponent of "defensive programming" and am willing to add extra logic into each area to ensure the program runs without any PHP notices or errors, maintains sane defaults, and will do extra work to prove a result is correct before returning it. We live in a world where the application tier can scale horizontally very easily. I actually just discovered this link, and like the a uthors' Eight Defensive Programmer Strategies: http://c.learncodethehardway.org/book/ex27.html

# Globals

Ones you'll notice and should care about:

* $config - all configuration values from config.php as well as some programmatically created on-the-fly.
* $request - created under web context. all aspects related to the current request. useful for extracting parts of the URI, argument parsing, etc.
* $user - created under web context. the current user logged in (or an anonymous skeleton if logged out.)

Behind the scenes (just for reference, you shouldn't care about them)

* $dbh - database resource handle.
* $ch - cache resource handle.

# Roughly working or stubbed out

* Per-page messaging (previous form validation message, for example)
* Theme concept

# Roadmap

* Form API
** field types
** validation hooks
** error submission array for easy theming
* Messaging framework
** extensible idea for email, SMS, various "message" types
** driven by easily editable templates
* Switch to PDO or use prepared statements
** PDO will make different backend options easier, but do we care?
