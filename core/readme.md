## Main README file for developers. Start here.

### SQL Issue for Latest Versions:
Need to run this for future versions.
SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));

### Folder structure:
- /core, contains 99% of all php code, and javascript and css source files (and a few other things). It should contain an .htaccess to deny all requests to its contents.
- A few front facing PHP files sit in the root directory. Usually these are very short "proxy" type files that invoke some logic from within /core. These files rarely change.
- /core/pages is where you'll find most pages. See core/router.php for more info.
- /core/templates contains header, footer, and some other things.
- /build, for compiled javascript and css files. Edits to css/js done inside of core/css, and core.js.
- /blog contains a wordpress install, but if you "git clone" you'll just have some theme files (installing wordpress is not required for most dev work)
- /assets, just for images right now.
- /lib has some (not in use?) moneris php library. All other libraries managed via composer.

### Deployment:

No automated deployment setup right now. Just SFTP and manually make changes to /core and /build  (as needed).

If you alter some images, then maybe also assets/images.

It's rare that any changes are made outside of these folders.

Ideally, all changes should be tracked in the git repo, and the master branch should be in sync with production. If you don't have access feel free to request it. 

### CSS

I use php storm file watcher to compile and minify css.

Install dependencies:
 
npm install -g lessc (I think lessc, if that doesn't work, just do less)
npm install less-plugin-clean-css -g (for minification using --clean-css argument)

Then setup a file watcher to call: 

lessc --no-color --clean-css="--s1 --advanced --compatibility=ie8" $FileName$

(The above didn't work for me one time, maybe also try this) (also, omit lessc if command points to a file)

--no-color --clean-css="--s1 --advanced --compatibility=ie8" $FileName$ $ProjectFileDir$/build/master.css

See core/readme's/phpstorm-less-file-watcher.JPG

### JS

Use "npm run js" (or "gulp watch") to watch and compile javascript files.

Make sure you install dev dependencies from package.json first.

### Env.php and .htaccess files

Each environment needs its own env.php file in the root directory. 

In developement, copy env--example.php into env.php and change things as needed. See the example file for more info.

In production, env.php most likely only needs one line of code: define( 'USE_LIVE_CONFIG', true);

env.php is git ignored (I think) but not the example file.

If you need to make changes to .htaccess in production, make it to .htaccess--live. Then copy that file to production and rename it. If you modify .htaccess in the git repo, then it will break dev environments.

### WordPress install at /blog

/blog contains a WordPress install. Everything else however is not WordPress. The WP install does however include core/_init.php which gives it access to all code used on the main site. This let's it re-use the exact same header and footer. It also loads the same css and javascript files (there is no separate css/js just for WP).

This does however mean that when coding for the main website you have to avoid naming collisions with functions/classes/etc in WordPress, otherwise WP will break, but the main site will not.

This is primarily why some functions and classes have a "cw_" prefix, while others do not.

### CLI

Command line interface more or less works but I don't use it too often. 

Ie. you can "php -a" and then "include /core/_init.php;" and then invoke any code as needed. This can be useful to create database tables in new environments.

### Creating a new database

There is some functions you can use to create the tables according to model definitions. See the init_db() function and DOING_DB_INIT constant. 

Sometimes its also easy enough to copy the production database and just delete some data.

### Maintenance mode

I almost never use this. But if a file exists in the root directory called maintenance-mode-indicator.php, then users will see a brief message
that the site is down for maintenance. In some cases I leave a file called maintenance-mode-indicator---off.php. There is a bypass for this, so
that you can launch and test changes to the site while normal users cannot see it. See core/other/maintenance.php.

### Other Readme's:

See core/readme's. Also contains some documentation text files and/or images. 

Contents of the folder should not be made publicly available. Having it in the core folders takes care of this.