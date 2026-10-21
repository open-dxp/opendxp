# Changelog

Every package of the OpenDXP ecosystem keeps a `CHANGELOG.md` in its root directory. It lists every change of every
release. A pull request adds its entry to the version that comes next.

A pull request that changes nothing a user notices, such as a change to the tests or to a workflow, carries the label
`no changelog` instead.

## The File

```markdown
# Changelog

## 1.5.0
- [NEW FEATURE] [HIGHLIGHT] `PUT /account` updates the first name, the last name and the locale of a user, see `docs/01_Authentication.md`
  > Users manage their own profile from any frontend. A custom endpoint is no longer needed.
- [BUGFIX] `/assets/{id}/thumbnail` returns 404 for a video instead of an empty response

## 1.4.2
- [BUGFIX] ...
```

- The file starts with `# Changelog`.
- Each version is a heading `## X.Y.Z`. The newest version is at the top.
- A pull request adds its entry to the top section. When the next version is not known yet, the top section is
  `## [Unreleased]`, as in [Keep a Changelog](https://keepachangelog.com).
- The release turns `## [Unreleased]` into the heading of its version. When a version is tagged, the top version in
  the file is the version of the tag.

## An Entry

```markdown
- [TYPE] [MARKER] Text
  > Announcement
  1. Step
  2. Step
```

An entry is one line. A long line may wrap onto the next line, indented by two spaces. A nested list is not allowed. A
second change is a second entry.

### Type

Every entry starts with exactly one type.

| Type            | The entry describes                                                    |
|-----------------|------------------------------------------------------------------------|
| `[NEW FEATURE]` | something that did not exist before                                    |
| `[ENHANCEMENT]` | something that exists and can do more or works better                  |
| `[BUGFIX]`      | something that behaves as it was always meant to                       |
| `[SECURITY]`    | a closed security vulnerability                                        |
| `[DEPRECATION]` | something that goes with the next major release and has a replacement  |
| `[BC BREAK]`    | a change that existing code or configuration has to follow             |
| `[CHORE]`       | clean-up, dependencies, tests and internal changes                     |

A minor or a patch release breaks nothing. A `[BC BREAK]` belongs to a major release only. A `[BC BREAK]` under
`## [Unreleased]` makes the next release a major one.

### Markers

Markers follow the type. An entry has none, one or several.

| Marker        | The entry                                                                                                                                  | Under the entry  |
|---------------|--------------------------------------------------------------------------------------------------------------------------------------------|------------------|
| `[HIGHLIGHT]` | belongs in the announcement of the release                                                                                                 | the announcement |
| `[OPT-IN]`    | brings something that stays off until a project turns it on, or a project keeps the previous behaviour with it                             | numbered steps   |
| `[FOLLOW-UP]` | needs a step after the update, such as clearing a cache or rebuilding an index. Without it nothing breaks, but something stays out of date | numbered steps   |
| `[MIGRATION]` | brings a Doctrine migration                                                                                                                | nothing          |

A `[BC BREAK]` always has numbered steps.

A migration is never a step. `[MIGRATION]` says that the package brings one, and the command follows from the package.

### Announcement

The announcement of a `[HIGHLIGHT]` is a line `  > ...` under the entry.

1. It has one or two sentences, written for a reader who does not code.
2. It says what a person can do with the change, not how it is built.
3. It may sound enthusiastic, but it claims nothing the entry does not say.

### Steps

Steps are numbered lines `  1. ...` under the entry.

1. Every step is an instruction: `Run`, `Add`, `Replace`, `Remove`.
2. A step names exactly what to do: the command, the configuration key, the file.
3. The steps follow the order in which they are done.
4. When the steps apply to some projects only, the first step names the condition: `Only if your project ...`.
   Without a condition, every project does them.

```markdown
- [CHORE] [OPT-IN] Dailymotion is no longer one of the default video types
  1. Only if your project uses Dailymotion: add `video_types.dailymotion` to the `config_parameter` of the video area.
  2. Add the template `type_dailymotion.html.twig` to your layout.
  3. Add a Dailymotion player to your frontend.
- [CHORE] [FOLLOW-UP] The full page cache keeps old entries of `/robots.txt` after the update
  1. Run `bin/console opendxp:cache:clear --tags=output,output_lifetime`.
```

## Writing an Entry

An entry is read by people who do not know the code. It has to make sense without the pull request.

1. It describes the state after the release, in the present tense. It does not describe what somebody did.
   Not `Provide default translations for permission keys`, but `The permission keys have default translations`.
2. It starts with the thing it is about, not with a verb.
3. A bug fix names the correct behaviour and the previous one: `returns 404 for a video instead of an empty response`.
4. Code is set in backticks: classes, methods, routes, configuration keys, commands and files.
5. A link to the documentation follows at the end with `see`, when a page exists.
6. It does not use `now` or `we`. The version says when.
7. It does not promote. Words like `powerful`, `seamless` or `easily` belong in an announcement, if anywhere.
8. A sentence that has to be read twice is rewritten.

## Deprecations

A deprecation has two parts:

1. The code marks it.
   - A class, a method, a constant or a property carries
     `@deprecated since 1.5 and will be removed in 2.0, use X instead`.
   - A configuration key, which cannot carry `@deprecated`, calls `->setDeprecated()` in the configuration tree.
2. The changelog has a `[DEPRECATION]` entry.

`trigger_deprecation()` is optional. It belongs where a message at runtime helps, and stays out where it would flood
the log.

## Checks

A pull request runs the workflow `Changelog` of
[open-dxp/workflows-collection-public](https://github.com/open-dxp/workflows-collection-public):

- **Changelog Enforcer** fails when the pull request does not change `CHANGELOG.md` and does not carry the label
  `no changelog`.
- **Changelog Lint** fails when `CHANGELOG.md` breaks the rules on this page, and when the next version is no major
  release but has a `[BC BREAK]`. It reports the line and the reason.

The review checks the wording.
