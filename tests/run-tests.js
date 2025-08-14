#!/usr/bin/env node
/**
 * CrawlGuard WP Test Runner
 * Orchestrates all test suites and generates reports
 */

const { spawn } = require('child_process');
const fs = require('fs').promises;
const path = require('path');
const chalk = require('chalk');

// Load environment variables
require('dotenv').config({ path: path.join(__dirname, '.env.test') });

class TestRunner {
  constructor() {
    this.results = {
      timestamp: new Date().toISOString(),
      environment: process.env.NODE_ENV || 'test',
      suites: {},
      summary: {
        passed: 0,
        failed: 0,
        skipped: 0,
        total: 0,
      },
    };
  }

  /**
   * Run a test suite
   */
  async runSuite(name, command, args = []) {
    console.log(chalk.blue(`\n📦 Running ${name}...\n`));
    
    return new Promise((resolve) => {
      const startTime = Date.now();
      const child = spawn(command, args, {
        stdio: 'inherit',
        shell: true,
        cwd: path.join(__dirname, '..'),
      });

      child.on('close', (code) => {
        const duration = Date.now() - startTime;
        const passed = code === 0;
        
        this.results.suites[name] = {
          passed,
          exitCode: code,
          duration: `${(duration / 1000).toFixed(2)}s`,
        };

        if (passed) {
          this.results.summary.passed++;
          console.log(chalk.green(`✅ ${name} passed in ${duration / 1000}s`));
        } else {
          this.results.summary.failed++;
          console.log(chalk.red(`❌ ${name} failed with code ${code}`));
        }

        this.results.summary.total++;
        resolve(code);
      });

      child.on('error', (error) => {
        console.error(chalk.red(`Error running ${name}:`, error));
        this.results.suites[name] = {
          passed: false,
          error: error.message,
        };
        this.results.summary.failed++;
        this.results.summary.total++;
        resolve(1);
      });
    });
  }

  /**
   * Run all test suites
   */
  async runAll() {
    console.log(chalk.cyan('🚀 Starting CrawlGuard WP Test Suite\n'));
    console.log(chalk.gray('Environment:', process.env.NODE_ENV));
    console.log(chalk.gray('API URL:', process.env.API_BASE_URL));
    console.log(chalk.gray('Test Mode:', process.env.TEST_MODE));
    
    const suites = [
      // JavaScript Unit Tests
      {
        name: 'JavaScript Unit Tests',
        command: 'npm',
        args: ['run', 'test:unit'],
      },
      // JavaScript Integration Tests
      {
        name: 'JavaScript Integration Tests',
        command: 'npm',
        args: ['run', 'test:integration'],
      },
      // API Tests
      {
        name: 'API Tests',
        command: 'npm',
        args: ['run', 'test:api'],
      },
      // E2E Tests (if not in CI)
      ...(process.env.CI ? [] : [{
        name: 'E2E Tests',
        command: 'npm',
        args: ['run', 'test:e2e'],
      }]),
      // PHP Tests (if PHPUnit is installed)
      ...(await this.isCommandAvailable('phpunit') ? [{
        name: 'PHP Unit Tests',
        command: 'phpunit',
        args: ['-c', 'tests/phpunit.xml', '--testsuite=Unit Tests'],
      }] : []),
    ];

    // Run tests sequentially or in parallel based on configuration
    if (process.env.TEST_PARALLEL === 'true') {
      await Promise.all(
        suites.map(suite => 
          this.runSuite(suite.name, suite.command, suite.args)
        )
      );
    } else {
      for (const suite of suites) {
        await this.runSuite(suite.name, suite.command, suite.args);
      }
    }

    // Generate report
    await this.generateReport();
    
    // Print summary
    this.printSummary();

    // Exit with appropriate code
    process.exit(this.results.summary.failed > 0 ? 1 : 0);
  }

  /**
   * Check if a command is available
   */
  async isCommandAvailable(command) {
    try {
      await new Promise((resolve, reject) => {
        const child = spawn('where', [command], { shell: true });
        child.on('close', code => code === 0 ? resolve() : reject());
      });
      return true;
    } catch {
      return false;
    }
  }

  /**
   * Generate test report
   */
  async generateReport() {
    const reportDir = path.join(__dirname, 'coverage');
    await fs.mkdir(reportDir, { recursive: true });
    
    const reportPath = path.join(
      reportDir,
      `test-report-${Date.now()}.json`
    );
    
    await fs.writeFile(
      reportPath,
      JSON.stringify(this.results, null, 2)
    );
    
    console.log(chalk.gray(`\n📄 Report saved to: ${reportPath}`));
  }

  /**
   * Print test summary
   */
  printSummary() {
    console.log(chalk.cyan('\n📊 Test Summary\n'));
    console.log(chalk.white('═'.repeat(50)));
    
    const { passed, failed, skipped, total } = this.results.summary;
    
    if (failed === 0) {
      console.log(chalk.green(`✅ All tests passed! (${passed}/${total})`));
    } else {
      console.log(chalk.red(`❌ ${failed} test suite(s) failed`));
      console.log(chalk.green(`✅ ${passed} test suite(s) passed`));
      if (skipped > 0) {
        console.log(chalk.yellow(`⏭️  ${skipped} test suite(s) skipped`));
      }
    }
    
    console.log(chalk.white('═'.repeat(50)));
    
    // Show failed suites
    const failedSuites = Object.entries(this.results.suites)
      .filter(([_, result]) => !result.passed);
    
    if (failedSuites.length > 0) {
      console.log(chalk.red('\nFailed Suites:'));
      failedSuites.forEach(([name, result]) => {
        console.log(chalk.red(`  • ${name} (exit code: ${result.exitCode})`));
      });
    }
  }
}

// Handle command line arguments
const args = process.argv.slice(2);
const runner = new TestRunner();

if (args.includes('--help')) {
  console.log(`
CrawlGuard WP Test Runner

Usage: node run-tests.js [options]

Options:
  --help        Show this help message
  --parallel    Run tests in parallel
  --suite       Run specific test suite
  --coverage    Generate coverage report

Examples:
  node run-tests.js
  node run-tests.js --parallel
  node run-tests.js --suite unit
  `);
  process.exit(0);
}

// Run tests
runner.runAll().catch(error => {
  console.error(chalk.red('Fatal error:', error));
  process.exit(1);
});
