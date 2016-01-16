# Travis CI to Canvas proxy

**A small PHP proxy application for adding grades into Canvas after Travis CI checks pass.**

Students will fork assignment repositories from GitHub, make their changes, and create a pull request. The pull request will trigger a series of tests with TravisCI.

After those tests pass, this application is called with specific information to mark their assignment complete inside Canvas.

---

## Quick setup

It’s a small single page application that expects a query string of parameters. It’s capable of running on Google App Engine, but is not necessary.

☛ `config.example.php` — Rename to just `config.php` and enter your Canvas API authentication key.

```php
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

- `gh_user` — The GitHub username, should match an entry in `$user_map`
- `gh_repo` — The GitHub repo, in the format of `user/repo`
- `gh_pr` — The pull request ID, a number
- `canvas_course` — The Canvas course ID number
- `canvas_assignment` — The Canvas assignment ID number

**Example request**

```
/grade?gh_user=thomasjbradley&gh_repo=acgd-webdev-1%2Ffork-pass-tests&gh_pr=12&canvas_course=123456&canvas_assignment=1234567
```

### Within Travis CI

The above information is available from with Travis:

- `gh_user` — Use `git rev-parse --abbrev-ref HEAD` command to grab the current branch, then parse the GitHub username
- `gh_repo` — The `TRAVIS_REPO_SLUG` environment variable
- `gh_pr` — The `TRAVIS_PULL_REQUEST` environment variable

The other information I’d store in the `package.json` file of your repository.

- `canvas_assignment` — I put this as the `name` entry
- `canvas_course` — Create a new entry called `canvas-course` or something

[**Check out my auto marking template.**](https://github.com/thomasjbradley/auto-marking-template)

---

## License & copyright

© 2016 Thomas J Bradley — Licensed under the [MIT License](LICENSE).
