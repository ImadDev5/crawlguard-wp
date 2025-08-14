const { exec } = require('child_process');
const path = require('path');

const services = [
  'services/bot-detection',
  'services/pricing-engine', 
  'services/content-licensing',
  'services/workflow-engine',
  'services/payment-processing',
  'services/analytics',
  'services/notification'
];

async function installDependencies() {
  console.log('🚀 Installing dependencies for all services...');
  
  for (const service of services) {
    const servicePath = path.join('arbiter-platform', service);
    console.log(`📦 Installing dependencies for ${service}...`);
    
    try {
      await new Promise((resolve, reject) => {
        exec('npm install', { cwd: servicePath }, (error, stdout, stderr) => {
          if (error) {
            console.error(`❌ Error installing ${service}:`, error);
            reject(error);
            return;
          }
          console.log(`✅ ${service} dependencies installed`);
          resolve();
        });
      });
    } catch (error) {
      console.error(`Failed to install ${service}:`, error.message);
    }
  }
  
  console.log('🎉 All service dependencies installed!');
}

installDependencies().catch(console.error);
