import { NextRequest, NextResponse } from 'next/server';
import crypto from 'crypto';
import { apiKeyStore } from '@/lib/apiKeyStore';

export async function POST(request: NextRequest) {
  try {
    // Generate a secure API key
    const apiKey = `ppk_${crypto.randomBytes(32).toString('hex')}`;
    
    // Store the API key using the shared store
    apiKeyStore.addKey(apiKey, {
      key: apiKey,
      createdAt: new Date(),
      userId: 'user_' + Math.random().toString(36).substring(7), // Mock user ID
      active: true
    });

    return NextResponse.json({
      success: true,
      apiKey,
      message: 'API key generated successfully'
    });
  } catch (error) {
    console.error('Error generating API key:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to generate API key' },
      { status: 500 }
    );
  }
}
