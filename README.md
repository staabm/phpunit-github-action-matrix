GitHub Action to parallelize a PHPUnit test suite over multiple GitHub Action jobs.

## Motivation

Unit test suites grow over time and at a certain point take a considerable amount of time to execute.
At this point there are several ways to scale your continous integration system with the goal to shorten the feedback loop again.

In comparison to existing PHPUnit parallelization plugins, this action distributes the load over several jobs and therefore utilizes more CPUs.
Other PHPUnit parallelization plugins are used to run tests in parallel, to saturate all available CPUs on a single host.

Running tests in parallel GitHub Action jobs has the advantage, that there is still only a single test running at a time in each job,
which means this approach does not necessarily need a test-suite which was designed with parallel execution in mind.

After segmenting a test-suite across multiple GitHub Action jobs, you may still/additionally use in-job parallelization with well known PHPUnit plugins.

## Input Parameters

### `strategy`

Strategy on how to segment the test suite. Supported values are `groups` and `suites`.

### `phpunit-path`

Path to PHPUnit executable. Default is `vendor/bin/phpunit`.
You may append additional parameters to the command, e.g. `vendor/bin/phpunit --condfiguration=path/to/phpunit.xml`.

## Example GitHub Actions workflow

The workflow shows a typical usage example within a GitHub Actions workflow which segments by test-suite.
You may adjust this workflow as you see fit to e.g.

- use a different Test Runner (e.g. ParaTest)
- segment per groups instead of suites
- in case you don't need dynamic building of the test-matrix you can hard-code your segments in `run-test-segment` and drop the `tests-segmentation` job.

```yaml
# https://help.github.com/en/categories/automating-your-workflow-with-github-actions

name: "Tests"

on:
  pull_request:

jobs:
  tests-segmentation:
    name: "Segment PHPUnit Test Suite"
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
        id: segmentation
        uses: staabm/phpunit-github-action-matrix@main
        with:
          phpunit-path: "vendor/bin/phpunit"
          strategy: "suites"
          
    outputs:
      phpunit-test-segments-json: ${{ steps.segmentation.outputs.segments-json }}

  run-test-segment:
    needs: tests-segmentation

    name: "Run PHPUnit segment ${{ matrix.suite-name }}"
    runs-on: ubuntu-latest
    timeout-minutes: 60

    strategy:
      fail-fast: false
      matrix:
        suite-name: "${{fromJson(needs.tests-segmentation.outputs.phpunit-test-segments-json)}}"

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
        run: "vendor/bin/phpunit --testsuite ${{ matrix.suite-name }}"

```

## Alternatives

After implementation was finished and we made same noise in social media regarding this repo, the following alternatives were mentioned which achieve similar things
- https://github.com/DaveLiddament/test-splitter - Splits PHPUnit tests into batches
