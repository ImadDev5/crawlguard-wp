/**
 * Jest Setup File
 * Configure test environment and global mocks
 */

// Load environment variables
require('dotenv').config({ path: '../.env' });

// Setup DOM environment
require('@testing-library/jest-dom');

// Global test utilities
global.fetchMock = require('jest-fetch-mock');
global.fetchMock.enableMocks();

// Mock WordPress globals
global.wp = {
  data: {
    select: jest.fn(),
    dispatch: jest.fn(),
    subscribe: jest.fn(),
  },
  api: {
    loadPromise: Promise.resolve(),
    models: {},
    collections: {},
  },
  element: {
    createElement: jest.fn(),
    render: jest.fn(),
  },
  i18n: {
    __: (text) => text,
    _x: (text) => text,
    _n: (single, plural, number) => number === 1 ? single : plural,
    sprintf: jest.fn(),
  },
  hooks: {
    addAction: jest.fn(),
    addFilter: jest.fn(),
    doAction: jest.fn(),
    applyFilters: jest.fn((name, value) => value),
    removeAction: jest.fn(),
    removeFilter: jest.fn(),
  },
};

// Mock jQuery
global.jQuery = global.$ = jest.fn((selector) => ({
  find: jest.fn().mockReturnThis(),
  click: jest.fn().mockReturnThis(),
  on: jest.fn().mockReturnThis(),
  off: jest.fn().mockReturnThis(),
  trigger: jest.fn().mockReturnThis(),
  addClass: jest.fn().mockReturnThis(),
  removeClass: jest.fn().mockReturnThis(),
  hasClass: jest.fn().mockReturnValue(false),
  attr: jest.fn().mockReturnThis(),
  prop: jest.fn().mockReturnThis(),
  val: jest.fn().mockReturnThis(),
  html: jest.fn().mockReturnThis(),
  text: jest.fn().mockReturnThis(),
  show: jest.fn().mockReturnThis(),
  hide: jest.fn().mockReturnThis(),
  fadeIn: jest.fn().mockReturnThis(),
  fadeOut: jest.fn().mockReturnThis(),
  ajax: jest.fn(),
}));

// Mock AJAX
global.jQuery.ajax = jest.fn(() => Promise.resolve({}));
global.jQuery.post = jest.fn(() => Promise.resolve({}));
global.jQuery.get = jest.fn(() => Promise.resolve({}));

// Mock WordPress AJAX
global.ajaxurl = '/wp-admin/admin-ajax.php';
global.crawlguard_ajax = {
  url: '/wp-admin/admin-ajax.php',
  nonce: 'test_nonce_12345',
};

// Mock localStorage
const localStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.localStorage = localStorageMock;

// Mock sessionStorage
const sessionStorageMock = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
global.sessionStorage = sessionStorageMock;

// Mock console methods for cleaner test output
const originalError = console.error;
const originalWarn = console.warn;

beforeAll(() => {
  console.error = jest.fn((message, ...args) => {
    if (
      typeof message === 'string' &&
      (message.includes('Warning: ReactDOM.render') ||
       message.includes('Warning: unmountComponentAtNode'))
    ) {
      return;
    }
    originalError(message, ...args);
  });
  
  console.warn = jest.fn((message, ...args) => {
    if (
      typeof message === 'string' &&
      message.includes('componentWillReceiveProps')
    ) {
      return;
    }
    originalWarn(message, ...args);
  });
});

afterAll(() => {
  console.error = originalError;
  console.warn = originalWarn;
});

// Clean up after each test
afterEach(() => {
  jest.clearAllMocks();
  localStorageMock.clear();
  sessionStorageMock.clear();
  fetchMock.resetMocks();
});

// Test environment configuration
process.env.NODE_ENV = 'test';
process.env.API_BASE_URL = process.env.API_BASE_URL || 'https://api.paypercrawl.tech/v1';
process.env.TEST_MODE = 'true';
