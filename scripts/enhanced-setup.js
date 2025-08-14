// Enhanced Prototype Setup Script
const fs = require('fs-extra');
const path = require('path');
const { exec } = require('child_process');

console.log('🚀 Setting up Enhanced Arbiter Platform Prototype...');

const createEnhancedStructure = async () => {
  const baseDir = process.cwd();
  
  // Enhanced project structure
  const structure = {
    'frontend': {
      'src': {
        'components': {
          'common': ['Header.tsx', 'Footer.tsx', 'Sidebar.tsx', 'Modal.tsx', 'Loading.tsx'],
          'creator': [
            'CreatorDashboard.tsx',
            'ContentUpload.tsx', 
            'LicenseManager.tsx',
            'RevenueAnalytics.tsx',
            'ContentLibrary.tsx',
            'UsageMonitor.tsx'
          ],
          'ai-company': [
            'AIDashboard.tsx',
            'ContentMarketplace.tsx',
            'LicensePurchase.tsx',
            'APIManager.tsx',
            'UsageAnalytics.tsx',
            'BillingCenter.tsx'
          ],
          'auth': ['Login.tsx', 'Register.tsx', 'Profile.tsx']
        },
        'pages': [
          'Home.tsx',
          'CreatorPortal.tsx', 
          'AICompanyPortal.tsx',
          'Marketplace.tsx',
          'Analytics.tsx'
        ],
        'hooks': [
          'useAuth.ts',
          'useContent.ts',
          'useLicense.ts',
          'usePayment.ts',
          'useAnalytics.ts'
        ],
        'services': [
          'api.ts',
          'auth.ts',
          'content.ts',
          'payment.ts',
          'analytics.ts'
        ],
        'types': [
          'auth.ts',
          'content.ts',
          'license.ts',
          'payment.ts',
          'analytics.ts'
        ],
        'utils': [
          'helpers.ts',
          'validation.ts',
          'formatting.ts',
          'constants.ts'
        ],
        'assets': {
          'images': ['logo.png', 'creator-hero.jpg', 'ai-hero.jpg'],
          'icons': ['upload.svg', 'analytics.svg', 'payment.svg']
        }
      },
      'public': ['index.html', 'favicon.ico', 'manifest.json']
    },
    'backend': {
      'api-gateway': {
        'src': [
          'index.ts',
          'routes.ts',
          'middleware.ts',
          'validation.ts'
        ]
      },
      'services': {
        'auth': {
          'src': [
            'index.ts',
            'authController.ts',
            'authService.ts',
            'middleware.ts'
          ]
        },
        'content': {
          'src': [
            'index.ts',
            'contentController.ts',
            'contentService.ts',
            'uploadHandler.ts',
            'metadataExtractor.ts'
          ]
        },
        'payment': {
          'src': [
            'index.ts',
            'paymentController.ts',
            'paymentService.ts',
            'stripeHandler.ts',
            'invoiceGenerator.ts'
          ]
        },
        'analytics': {
          'src': [
            'index.ts',
            'analyticsController.ts',
            'analyticsService.ts',
            'reportGenerator.ts',
            'dashboardData.ts'
          ]
        },
        'notification': {
          'src': [
            'index.ts',
            'notificationController.ts',
            'notificationService.ts',
            'emailHandler.ts',
            'smsHandler.ts'
          ]
        },
        'bot-detection': {
          'src': [
            'index.ts',
            'detectionController.ts',
            'detectionService.ts',
            'mlModel.ts',
            'ruleEngine.ts'
          ]
        },
        'pricing': {
          'src': [
            'index.ts',
            'pricingController.ts',
            'pricingService.ts',
            'dynamicPricing.ts',
            'priceCalculator.ts'
          ]
        },
        'licensing': {
          'src': [
            'index.ts',
            'licensingController.ts',
            'licensingService.ts',
            'licenseGenerator.ts',
            'complianceChecker.ts'
          ]
        }
      },
      'shared': {
        'types': ['common.ts', 'api.ts', 'database.ts'],
        'utils': ['helpers.ts', 'validation.ts', 'logger.ts'],
        'middleware': ['auth.ts', 'rate-limit.ts', 'cors.ts']
      }
    },
    'database': {
      'migrations': ['001_initial.sql', '002_indexes.sql', '003_functions.sql'],
      'seeds': ['demo-creators.sql', 'demo-ai-companies.sql', 'demo-content.sql'],
      'schemas': ['users.sql', 'content.sql', 'licenses.sql', 'payments.sql']
    },
    'docker': {
      'nginx': ['nginx.conf', 'ssl/'],
      'prometheus': ['prometheus.yml'],
      'grafana': ['dashboards/', 'datasources/']
    },
    'scripts': [
      'enhanced-setup.js',
      'demo-data.js',
      'migrate.js',
      'deploy.js'
    ],
    'tests': {
      'unit': ['auth.test.ts', 'content.test.ts', 'payment.test.ts'],
      'integration': ['api.test.ts', 'workflow.test.ts'],
      'e2e': ['creator-journey.spec.ts', 'ai-company-journey.spec.ts']
    },
    'docs': [
      'API.md',
      'CREATOR_GUIDE.md', 
      'AI_COMPANY_GUIDE.md',
      'DEPLOYMENT.md',
      'TESTING.md'
    ]
  };

  // Create directory structure
  const createStructure = async (obj, basePath = '') => {
    for (const [key, value] of Object.entries(obj)) {
      const fullPath = path.join(baseDir, basePath, key);
      
      if (Array.isArray(value)) {
        // Create directory and files
        await fs.ensureDir(fullPath);
        for (const file of value) {
          const filePath = path.join(fullPath, file);
          if (!await fs.pathExists(filePath)) {
            await fs.createFile(filePath);
            console.log(`📄 Created: ${path.relative(baseDir, filePath)}`);
          }
        }
      } else if (typeof value === 'object') {
        // Create directory and recurse
        await fs.ensureDir(fullPath);
        await createStructure(value, path.join(basePath, key));
      }
    }
  };

  await createStructure(structure);
  console.log('✅ Enhanced project structure created!');
};

// Create enhanced frontend package.json
const createFrontendPackage = async () => {
  const frontendPackage = {
    "name": "@arbiter/frontend-enhanced",
    "version": "2.0.0",
    "description": "Enhanced Creator and AI Company Portal",
    "scripts": {
      "dev": "vite",
      "build": "vite build",
      "preview": "vite preview",
      "test": "jest",
      "test:e2e": "cypress open",
      "lint": "eslint src --ext .ts,.tsx",
      "lint:fix": "eslint src --ext .ts,.tsx --fix",
      "type-check": "tsc --noEmit"
    },
    "dependencies": {
      "react": "^18.3.1",
      "react-dom": "^18.3.1",
      "react-router-dom": "^6.28.0",
      "react-query": "^3.39.3",
      "react-hook-form": "^7.53.0",
      "react-dropzone": "^14.2.9",
      "@hookform/resolvers": "^3.9.0",
      "yup": "^1.4.0",
      "axios": "^1.7.7",
      "socket.io-client": "^4.8.0",
      "chart.js": "^4.4.4",
      "react-chartjs-2": "^5.2.0",
      "date-fns": "^4.1.0",
      "react-select": "^5.8.1",
      "react-table": "^7.8.0",
      "react-hot-toast": "^2.4.1",
      "framer-motion": "^11.9.0",
      "lucide-react": "^0.446.0",
      "@headlessui/react": "^2.1.9",
      "@heroicons/react": "^2.1.5",
      "clsx": "^2.1.1",
      "tailwind-merge": "^2.5.2"
    },
    "devDependencies": {
      "@types/react": "^18.3.11",
      "@types/react-dom": "^18.3.0",
      "@vitejs/plugin-react": "^4.3.2",
      "vite": "^5.4.8",
      "typescript": "^5.6.2",
      "tailwindcss": "^3.4.13",
      "autoprefixer": "^10.4.20",
      "postcss": "^8.4.47",
      "@types/node": "^22.7.4",
      "eslint": "^9.11.1",
      "@typescript-eslint/eslint-plugin": "^8.8.0",
      "@typescript-eslint/parser": "^8.8.0",
      "jest": "^29.7.0",
      "@types/jest": "^29.5.13",
      "cypress": "^13.15.0"
    }
  };

  await fs.writeJSON(path.join(process.cwd(), 'frontend', 'package.json'), frontendPackage, { spaces: 2 });
  console.log('✅ Enhanced frontend package.json created!');
};

// Create demo data script
const createDemoData = async () => {
  const demoScript = `
// Demo Data Generator for Enhanced Prototype
const { PrismaClient } = require('@prisma/client');
const bcrypt = require('bcryptjs');

const prisma = new PrismaClient();

const createDemoData = async () => {
  console.log('🎯 Creating demo data for enhanced prototype...');

  // Demo Creators
  const creators = await Promise.all([
    prisma.user.create({
      data: {
        email: 'creator1@demo.com',
        password: await bcrypt.hash('demo123', 10),
        name: 'Sarah Johnson',
        type: 'CREATOR',
        profile: {
          create: {
            bio: 'Professional photographer and digital artist',
            avatar: 'https://images.unsplash.com/photo-1494790108755-2616b612b4e0',
            website: 'https://sarahjohnsonphoto.com',
            social: {
              instagram: '@sarahjphoto',
              twitter: '@sarahj_creates'
            }
          }
        }
      }
    }),
    prisma.user.create({
      data: {
        email: 'creator2@demo.com', 
        password: await bcrypt.hash('demo123', 10),
        name: 'Marcus Chen',
        type: 'CREATOR',
        profile: {
          create: {
            bio: 'Video content creator and motion graphics designer',
            avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d',
            website: 'https://marcuschen.design'
          }
        }
      }
    })
  ]);

  // Demo AI Companies
  const aiCompanies = await Promise.all([
    prisma.user.create({
      data: {
        email: 'ai-company1@demo.com',
        password: await bcrypt.hash('demo123', 10), 
        name: 'TechVision AI',
        type: 'AI_COMPANY',
        profile: {
          create: {
            bio: 'Leading computer vision AI company',
            avatar: 'https://images.unsplash.com/photo-1551434678-e076c223a692',
            website: 'https://techvision-ai.com',
            companySize: 'LARGE',
            industry: 'Computer Vision'
          }
        }
      }
    }),
    prisma.user.create({
      data: {
        email: 'ai-company2@demo.com',
        password: await bcrypt.hash('demo123', 10),
        name: 'NLP Innovations',
        type: 'AI_COMPANY', 
        profile: {
          create: {
            bio: 'Natural language processing startup',
            avatar: 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43',
            website: 'https://nlp-innovations.com',
            companySize: 'STARTUP',
            industry: 'Natural Language Processing'
          }
        }
      }
    })
  ]);

  // Demo Content
  const content = [
    {
      title: 'Urban Photography Collection',
      description: 'High-quality urban street photography for AI training',
      type: 'IMAGE',
      category: 'Photography',
      tags: ['urban', 'street', 'photography', 'city'],
      creatorId: creators[0].id,
      price: 299.99,
      licenseType: 'COMMERCIAL'
    },
    {
      title: 'Motion Graphics Templates',
      description: 'Professional motion graphics for video AI models',
      type: 'VIDEO',
      category: 'Animation',
      tags: ['motion', 'graphics', 'animation', 'template'],
      creatorId: creators[1].id,
      price: 499.99,
      licenseType: 'COMMERCIAL'
    }
  ];

  for (const item of content) {
    await prisma.content.create({ data: item });
  }

  // Demo Transactions
  await prisma.transaction.create({
    data: {
      amount: 299.99,
      buyerId: aiCompanies[0].id,
      sellerId: creators[0].id,
      contentId: (await prisma.content.findFirst()).id,
      status: 'COMPLETED',
      stripePaymentId: 'pi_demo_123'
    }
  });

  console.log('✅ Demo data created successfully!');
  console.log('👥 Demo Accounts:');
  console.log('📸 Creator 1: creator1@demo.com / demo123');
  console.log('🎬 Creator 2: creator2@demo.com / demo123');
  console.log('🤖 AI Company 1: ai-company1@demo.com / demo123');
  console.log('🧠 AI Company 2: ai-company2@demo.com / demo123');
};

createDemoData().catch(console.error).finally(() => prisma.$disconnect());
`;

  await fs.writeFile(path.join(process.cwd(), 'scripts', 'demo-data.js'), demoScript);
  console.log('✅ Demo data script created!');
};

// Execute setup
(async () => {
  try {
    await createEnhancedStructure();
    await createFrontendPackage();
    await createDemoData();
    
    console.log('🎉 Enhanced prototype setup complete!');
    console.log('📋 Next Steps:');
    console.log('1. Wait for WSL setup to complete');
    console.log('2. Copy project to WSL: wsl cp -r /mnt/c/Users/ADMIN/OneDrive/Desktop/plugin/* ~/arbiter-platform/');
    console.log('3. Run: bash setup-wsl.sh');
    console.log('4. Run: npm run prototype:demo');
    console.log('5. Test both creator and AI company portals');
    
  } catch (error) {
    console.error('❌ Setup failed:', error);
  }
})();
