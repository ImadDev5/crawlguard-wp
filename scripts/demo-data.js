
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
