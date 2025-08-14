#!/usr/bin/env node

/**
 * Rules Engine Test Script
 * Demonstrates the core functionality of the Arbiter Platform Rules Engine
 */

import axios from 'axios';

const RULES_ENGINE_URL = 'http://localhost:3020';
const TEST_TOKEN = 'test-token'; // Mock token for testing

// Test data
const sampleRequest = {
  domain: 'techblog.com',
  url: 'https://techblog.com/ai-article.html',
  botId: 'GPTBot',
  userAgent: 'Mozilla/5.0 (compatible; GPTBot/1.0; +https://openai.com/bot)',
  ipAddress: '192.168.1.100',
  referer: 'https://openai.com',
  contentType: 'text/html',
  requestMethod: 'GET',
  headers: {
    'Accept': 'text/html,application/xhtml+xml',
    'Accept-Language': 'en-US,en;q=0.9'
  },
  metadata: {
    crawlReason: 'training_data',
    priority: 'high'
  }
};

const sampleRule = {
  name: 'Premium AI Access',
  description: 'Charge premium rates for AI training data',
  conditions: [
    {
      type: 'BOT_ID',
      operator: 'CONTAINS',
      value: 'GPT'
    },
    {
      type: 'CONTENT_TYPE',
      operator: 'EQUALS',
      value: 'text/html'
    }
  ],
  actions: [
    {
      type: 'SET_PRICE',
      value: 0.05
    },
    {
      type: 'REQUIRE_LICENSE',
      value: true
    }
  ],
  priority: 100,
  isActive: true
};

async function testRulesEngine() {
  console.log('🧪 Testing Arbiter Platform Rules Engine');
  console.log('=====================================\n');

  try {
    // Test 1: Health Check
    console.log('1️⃣  Testing Health Check...');
    const healthResponse = await axios.get(`${RULES_ENGINE_URL}/health`);
    console.log('✅ Health Status:', healthResponse.data.status);
    console.log();

    // Test 2: Rule Templates
    console.log('2️⃣  Fetching Rule Templates...');
    const templatesResponse = await axios.get(`${RULES_ENGINE_URL}/api/rules/templates`);
    console.log('✅ Templates Available:', templatesResponse.data.data.templates.length);
    console.log('📋 Templates:', templatesResponse.data.data.templates.map(t => t.name));
    console.log();

    // Test 3: Rule Evaluation
    console.log('3️⃣  Testing Rule Evaluation...');
    const evaluateResponse = await axios.post(
      `${RULES_ENGINE_URL}/api/rules/evaluate`,
      sampleRequest,
      {
        headers: {
          'Authorization': `Bearer ${TEST_TOKEN}`,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const result = evaluateResponse.data.data.result;
    console.log('✅ Evaluation Result:');
    console.log('   📊 Matched:', result.matched);
    console.log('   🎯 Matched Rules:', result.matchedRules.length);
    console.log('   ⚡ Actions:', result.actions.length);
    console.log('   💰 Pricing:', result.pricing);
    console.log('   ⏱️  Evaluation Time:', result.evaluationTime + 'ms');
    console.log();

    // Test 4: Rule Testing
    console.log('4️⃣  Testing Rule Conditions...');
    const testResponse = await axios.post(
      `${RULES_ENGINE_URL}/api/rules/test`,
      {
        rule: sampleRule,
        request: sampleRequest
      },
      {
        headers: {
          'Authorization': `Bearer ${TEST_TOKEN}`,
          'Content-Type': 'application/json'
        }
      }
    );
    
    const testResult = testResponse.data.data;
    console.log('✅ Rule Test Result:');
    console.log('   🎯 Rule Matched:', testResult.matched);
    console.log('   ⚡ Actions Generated:', testResult.actions.length);
    console.log('   💰 Pricing Calculated:', testResult.pricing);
    console.log();

    // Test 5: Performance Test
    console.log('5️⃣  Performance Test (10 evaluations)...');
    const startTime = Date.now();
    const promises = [];
    
    for (let i = 0; i < 10; i++) {
      promises.push(
        axios.post(
          `${RULES_ENGINE_URL}/api/rules/evaluate`,
          {
            ...sampleRequest,
            url: `${sampleRequest.url}?test=${i}`
          },
          {
            headers: {
              'Authorization': `Bearer ${TEST_TOKEN}`,
              'Content-Type': 'application/json'
            }
          }
        )
      );
    }
    
    const results = await Promise.all(promises);
    const endTime = Date.now();
    
    console.log('✅ Performance Results:');
    console.log('   🚀 Total Time:', (endTime - startTime) + 'ms');
    console.log('   ⚡ Average per Request:', ((endTime - startTime) / 10) + 'ms');
    console.log('   📈 Requests/Second:', Math.round(10000 / (endTime - startTime)));
    console.log();

    console.log('🎉 All tests completed successfully!');
    console.log('🌟 Rules Engine is working perfectly!');
    
  } catch (error) {
    console.error('❌ Test failed:', error.response?.data || error.message);
    process.exit(1);
  }
}

// Run the tests
testRulesEngine().catch(console.error);
