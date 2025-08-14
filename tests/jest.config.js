/**
 * Jest Configuration for CrawlGuard WP Plugin
 * JavaScript and React component testing configuration
 */

module.exports = {
  // Test environment
  testEnvironment: 'jsdom',
  
  // Module name mapper for CSS and asset imports
  moduleNameMapper: {
    '\\.(css|less|scss|sass)$': '<rootDir>/tests/__mocks__/styleMock.js',
    '\\.(gif|ttf|eot|svg|png|jpg|jpeg)$': '<rootDir>/tests/__mocks__/fileMock.js',
  },
  
  // Setup files
  setupFilesAfterEnv: ['<rootDir>/tests/jest.setup.js'],
  
  // Test match patterns
  testMatch: [
    '<rootDir>/tests/**/*.test.js',
    '<rootDir>/tests/**/*.spec.js',
  ],
  
  // Coverage configuration
  collectCoverage: true,
  collectCoverageFrom: [
    'assets/js/**/*.{js,jsx}',
    'backend/**/*.js',
    '!**/node_modules/**',
    '!**/vendor/**',
    '!**/*.config.js',
  ],
  coverageDirectory: '<rootDir>/tests/coverage/jest',
  coverageReporters: ['text', 'lcov', 'html'],
  
  // Transform files
  transform: {
    '^.+\\.(js|jsx)$': ['babel-jest', {
      presets: ['@babel/preset-env', '@babel/preset-react']
    }],
  },
  
  // Ignore patterns
  testPathIgnorePatterns: [
    '/node_modules/',
    '/vendor/',
    '/tests/e2e/',
  ],
  
  // Globals
  globals: {
    API_BASE_URL: 'https://api.paypercrawl.tech/v1',
    WP_DEBUG: false,
  },
  
  // Verbose output
  verbose: true,
  
  // Test timeout
  testTimeout: 10000,
};
