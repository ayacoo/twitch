# TYPO3 Extension twitch

## 1 Features

* Twitch videos can be created as a file in the TYPO3 file list
* Twitch videos can be used and output with the text with media element

## 2 Usage

### 2.1 Prerequisites

Twitch unfortunately does not provide us with an oEmbed interface, so we have to query the data via an API. The setup is
done quite quickly, though.

First, you need a Twitch account with 2FA to be able to create an app with it via [Twitch Console][5].

The application only needs a name and 2-3 settings. Interesting for us is the ClientId, which we also need for the
communication. Client Secret must also be generated, because we need it for the generation of the token need.

After creating the application you still need a token. For this you can install the [Twitch CLI][6]. If everything is
installed, you can generate a token with ```twitch token``` and the input of Client ID and Client Secret. Please save
this token.

In the TYPO3 Install Tool you can now store the two settings "Token" and "Client Id". Without this data there will be
errors in the backend, and we will not get any data from Twitch.

### 2.2 Installation

#### Installation using Composer

The recommended way to install the extension is using Composer.

Run the following command within your [Composer][1] based TYPO3 project:

```
composer require ayacoo/twitch
```

### 2.3 Hints

#### Output

For the output, the HTML is used directly from [Twitch][4].

#### SQL changes

In order not to have to access the oEmbed interface permanently, one thumbnail field are added to the sys_file_metadata
table

#### TYPO3 v10

If you want to use the extension with TYPO3 v10, it should work in principle. The code is close to the v10. You can
create a repository via a Github fork and modify / add the necessary lines there.

#### Videos vs Clips

Clips are excerpts from videos. If you want to enter them specifically, you can control the entry via the Time
parameter. If you prefer to have the clips as data sets, you can also register a MediaViewHelper relatively easily. The
structure is comparable to the Twitch videos.

## 3 Administration corner

### 3.1 Versions and support

| Twitch | TYPO3 | PHP   | Support / Development                   |
|--------|-------|-------|---------------------------------------- |
| 1.x    | 11.x  | 8.0   | features, bugfixes, security updates    |

### 3.2 Release Management

twitch uses [**semantic versioning**][2], which means, that

* **bugfix updates** (e.g. 1.0.0 => 1.0.1) just includes small bugfixes or security relevant stuff without breaking
  changes,
* **minor updates** (e.g. 1.0.0 => 1.1.0) includes new features and smaller tasks without breaking changes,
* and **major updates** (e.g. 1.0.0 => 2.0.0) breaking changes which can be refactorings, features or bugfixes.

### 3.3 Contribution

**Pull Requests** are gladly welcome! Nevertheless please don't forget to add an issue and connect it to your pull
requests. This
is very helpful to understand what kind of issue the **PR** is going to solve.

**Bugfixes**: Please describe what kind of bug your fix solve and give us feedback how to reproduce the issue. We're
going
to accept only bugfixes if we can reproduce the issue.

## 4 Thanks / Notices

Special thanks to Georg Ringer and his [news][3] extension. A good template to build a TYPO3 extension. Here, for
example, the structure of README.md is used.


[1]: https://getcomposer.org/

[2]: https://semver.org/

[3]: https://github.com/georgringer/news

[4]: https://dev.twitch.tv/docs/embed

[5]: https://dev.twitch.tv/

[6]: https://dev.twitch.tv/docs/cli
