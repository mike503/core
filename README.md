# Why?

There's plenty of OO frameworks out there, however I am a purely procedural guy. I dislike namespaces, objects, and most of the newer PHP constructs. I think you can still make amazingly elegant things without them. Drupal 7 and previous versions are an example of an elegant procedurally-driven hook system.

There are so many different ways to do things. I like to create mature single purpose functions that provide the best experience with the best performance possible. I have been using bits and pieces of this "library" or "framework" for years, maturing them over time.

I am a heavy proponent of "defensive programming" and am willing to add extra logic into each area to ensure the program runs without any PHP notices or errors, maintains sane defaults, and will do extra work to prove a result is correct before returning it. We live in a world where the application tier can scale horizontally very easily. I actually just discovered this link, and like the a uthors' Eight Defensive Programmer Strategies: http://c.learncodethehardway.org/book/ex27.html

The goal of this is not only extremely robust, mature and reusable components, but a canonical way to do common tasks and be able to create fast, clean applications rapidly.

# Scope

Since PHP itself can be considered a "templating language" I don't want to implement many UI concepts, ideally. Theming/UI should be done separately.

The scope of this project is to build an efficient base layer for anything - it could be an API/service, it could be a full blown website.

The concept of "users" and "sessions" starts to become a grey area. In theory those should be second class "modules" that are only needed if users or sessions are desired. More work to separate that is needed.

# The "registry" concept

At the moment, there are no global variables that are referenced directly. Instead, there is a single global static array that includes all the "facts" about the current request. It pulls in all config values, normalized/scrubbed request input, and any other variables that are requested through the core_variable_X() functions.

I might change this - it seems like an awful lot of stuff is tied to a central place. The original idea was to include everything a request might need through this system, which in turn would make debugging very simple as well.

Ultimately, the idea is for everything to execute only once per request (at the most) and make it easy and consistent to interact with.

# Roughly working or stubbed out

* Per-page messaging (previous form validation message, for example)
* Theme concept
  * not sure I even like the idea of "themes" - this framework might do best by producing only the underlying data, and allowing users to do whatever they want in PHP on top of it

# Roadmap

* Stable 4xx and 5xx pages that can execute properly if they are missing or unavailable
* Form API
  * field types
  * validation hooks
  * error submission array for easy theming
* Notifications framework
  * extensible idea for email, SMS, various "message" types
  * driven by easily editable templates
    * email template concept is already functional
* Switch to PDO or use prepared statements
  * PDO will make different backend options easier

# Modules/pluggable ideas or "providers"

* Cache: memcache, memached, apcu, redis, database (I guess), file (maybe not), none (highly not recommended)
  * only one can be utilized/chosen per request
* Database: mysqli, PDO (probably should adopt that)
  * multiple can be initialized/chosen per request
* Logger: file, database, syslog, console/output/HTML/display (name TBD)
  * multiple can be utilized/chosen per request
  * basic framework is there, but should probably look into a hook_log() type concept
* Notifications: email, SMS, push, web (i.e. supporting jGrowl style)
  * multiple can be utilized/chosen per request
* Session: database (default right now), file, memcached/redis or $chosen_cache_provider
  * only one can be utilized/chosen per request
* HTTP / transport - curl, socket, fopen wrappers, guzzle, etc.
  * multiple can be utilized/chosen per request
