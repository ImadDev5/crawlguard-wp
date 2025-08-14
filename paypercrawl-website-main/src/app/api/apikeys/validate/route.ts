import { NextRequest, NextResponse } from 'next/server';
import { apiKeyStore } from '@/lib/apiKeyStore';

export async function POST(request: NextRequest) {
  try {
    const { apiKey } = await request.json();

    if (!apiKey) {
      return NextResponse.json(
        { success: false, error: 'API key is required' },
        { status: 400 }
      );
    }

    // Check if API key exists and is active using the shared store
    const keyData = apiKeyStore.getKey(apiKey);
    
    if (!keyData) {
      return NextResponse.json(
        { success: false, error: 'Invalid API key' },
        { status: 401 }
      );
    }

    if (!keyData.active) {
      return NextResponse.json(
        { success: false, error: 'API key is inactive' },
        { status: 403 }
      );
    }

    return NextResponse.json({
      success: true,
      valid: true,
      userId: keyData.userId,
      message: 'API key is valid'
    });
  } catch (error) {
    console.error('Error validating API key:', error);
    return NextResponse.json(
      { success: false, error: 'Failed to validate API key' },
      { status: 500 }
    );
  }
}
