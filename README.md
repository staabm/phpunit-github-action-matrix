GitHub Action to parallelize a PHPUnit test suite over multiple GitHub Action jobs.

In comparison to existing PHPUnit parallelization plugins, this action distributes the load over several jobs.

Other PHPUnit parallelization plugins are used to run tests in parallel on a single host, to saturate all available CPUs. 

## Input Parameters

### `strategie`

Strategy on how to segment the test suite. Supported values are `groups` and `suites`.

### `phpunit-path`

Path to PHPUnit executable. Default is `vendor/bin/phpunit`.
You may append additional parameters to the command, e.g. `vendor/bin/phpunit --condfiguration=path/to/phpunit.xml`.

## Example GitHub Actions workflow

```yaml
# https://help.github.com/en/categories/automating-your-workflow-with-github-actions

name: "Tests"

on:
  pull_request:

jobs:
  tests-matrix:
    name: "Determine tests matrix"
    runs-on: ubuntu-latest
    timeout-minutes: 10

    steps:
      - name: "Checkout"
        uses: actions/checkout@v4

      - name: "Install PHP"
        uses: "shivammathur/setup-php@v2"
        with:
          coverage: "none"
          php-version: "8.3"
          
      - name: "Install dependencies"
        run: "composer install --no-interaction --no-progress"

      - name: "Segment PHPUnit test-suite"
        id: set-matrix
        uses: staabm/phpunit-github-action-matrix@main
        with:
          phpunit-path: "vendor/bin/phpunit"
          strategy: "groups"
          
    outputs:
      phpunit-action-matrix-json: ${{ steps.set-matrix.outputs.phpunit-action-matrix-json }}

  run-test-segment:
    needs: tests-matrix

    name: "Run PHPUnit segment"
    runs-on: ubuntu-latest
    timeout-minutes: 60

    strategy:
      fail-fast: false
      matrix:
        phpunit-script: "${{fromJson(needs.tests-levels-matrix.outputs.phpunit-action-matrix-json)}}"

    steps:
      - name: "Checkout"
        uses: actions/checkout@v4

      - name: "Install PHP"
        uses: "shivammathur/setup-php@v2"
        with:
          coverage: "none"
          php-version: "8.3"

      - name: "Install dependencies"
        run: "composer install --no-interaction --no-progress"

      - name: "Tests"
        run: "${{ matrix.phpunit-script }}"

```