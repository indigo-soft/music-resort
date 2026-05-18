import {execSync} from 'node:child_process';

export default {
    rules: {
        // Scope is REQUIRED
        'scope-empty': [2, 'never'],
        'scope-case': [2, 'always', 'kebab-case'],
        'scope-enum': [
            2,
            'always',
            [
                'command',
                'service',
                'exception',
                'helpers',
                'lang',
                'config',
                'docs',
                'deps',
            ],
        ],

        // Type validation
        'type-case': [2, 'always', 'lower-case'],
        'type-empty': [2, 'never'],
        'type-enum': [
            2,
            'always',
            [
                'feat',
                'fix',
                'docs',
                'style',
                'refactor',
                'perf',
                'test',
                'chore',
                'ci',
                'build',
                'revert',
            ],
        ],

        // Subject validation
        'subject-case': [1, 'always', 'lower-case'],
        'subject-empty': [2, 'never'],
        'subject-full-stop': [2, 'never', '.'],
        'subject-max-length': [2, 'always', 120],

        // Body validation
        'body-leading-blank': [1, 'always'],
        'body-max-line-length': [2, 'always', 100],

        // Footer validation
        'footer-leading-blank': [1, 'always'],

        // Branch naming validation (custom rule)
        'branch-name-format': [2, 'always'],
    },
    plugins: [
        {
            rules: {
                'branch-name-format': (parsed, when = 'always', value = {}) => {
                    let branchName;

                    try {
                        branchName = execSync('git symbolic-ref --short HEAD', {
                            encoding: 'utf8',
                        }).trim();
                    } catch (error) {
                        return [true];
                    }

                    if (branchName === 'main' || branchName === 'master') {
                        return [true];
                    }

                    const branchPattern = /^(feature|fix|docs|refactor|test|chore|perf)\/[0-9]{4,}-[a-z0-9-]+$/;
                    const isValid = branchPattern.test(branchName);

                    if (!isValid) {
                        return [
                            false,
                            `❌ Invalid branch name: "${branchName}"

Expected format: <type>/<issue-number>-<short-description>

Rules:
  • Type: feature, fix, docs, refactor, test, chore, perf
  • Issue number: REQUIRED, minimum 4 digits (e.g., 0001, 0042, 1234)
  • Description: kebab-case (lowercase, words separated by hyphens)

✅ Valid examples:
  • feature/0001-resort-by-album
  • fix/0042-metadata-encoding
  • docs/0099-update-readme
  • chore/1234-update-dependencies

❌ Invalid examples:
  • feature/my-feature (missing issue number)
  • fix/1-bug (issue number < 4 digits)

See: docs/guides/git-workflow.md`,
                        ];
                    }

                    return [true];
                },
            },
        },
    ],
};
