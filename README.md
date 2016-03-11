# Markbot Server

**A small PHP proxy application for adding grades into Canvas after [Markbot](https://github.com/thomasjbradley/markbot) checks pass.**

Students will fork assignment repositories from GitHub, make their changes, and drop their folder into Markbot.

After those tests pass, this application is called with specific information to mark their assignment complete inside Canvas.

#### [Check out all the tests in the Markbot repo.](https://github.com/thomasjbradley/markbot)

---

## Why not build this into Markbot?

Well, I suppose I have a few reasons:

1. To keep my teacher Canvas API key secure, I’d rather it not be embedded in every Markbot build.
2. To control who can submit to Canvas by controlling the accepted list.
3. To avoid students having to figure out their Canvas user ID.

*In the first day of class I create a Canvas assignment where they hand in their GitHub profile URL.* I can then use the Canvas API to grab all those submissions and parse out the GitHub username and Canvas ID to generate the user map.

---

## Quick setup

It’s a small single page application that expects a query string of parameters. It’s capable of running on [Google App Engine](https://cloud.google.com/appengine/), but is not necessary.

☛ `config.example.php` — Rename to just `config.php` and enter your API authentication keys.

```php
$canvas_base_url = 'CANVAS_SUB_DOMAIN'; // example: algonquin.instructure.com
$canvas_api_key = 'CANVAS_API_KEY';
```

☛ `user-map.example.php` — Rename to just `user-map.php`. Fill with mappings of GitHub usernames to Canvas user IDs.

```php
$user_map = [
  'github-username' => 'canvas-id-number'
];
```

---

## Use

Make a `GET` request to the `grade.php` file (or the `/grade` route if using Google App Engine) with the following query string parameters:

- `gh_repo` — The GitHub repo name
- `gh_username` — The students GitHub username for matching against their Canvas ID
- `canvas_course` — The Canvas course ID number
- `canvas_assignment` — The Canvas assignment ID number
- `markbot_version` — The version of the Markbot app that sent this request—to help prevent students from using outdated versions

**Example request**

```
/grade?gh_repo=using-markbot&gh_username=thomasjbradley&canvas_assignment=1234567&canvas_course=123456&markbot_version=2.6.1
```

---

## License & copyright

© 2016 Thomas J Bradley — [MIT License](LICENSE).
