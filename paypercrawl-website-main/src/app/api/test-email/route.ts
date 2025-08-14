import { NextRequest, NextResponse } from 'next/server'
import { sendBetaInvite } from '@/lib/email'

export async function POST(request: NextRequest) {
  try {
    // Check admin authentication
    const authHeader = request.headers.get('authorization')
    const adminKey = authHeader?.replace('Bearer ', '')
    
    if (adminKey !== process.env.ADMIN_API_KEY) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    const { email, name, token } = await request.json()

    if (!email || !name || !token) {
      return NextResponse.json({ 
        error: 'Missing required fields: email, name, token' 
      }, { status: 400 })
    }

    console.log('Testing email send with:', { email, name, token })
    console.log('RESEND_API_KEY exists:', !!process.env.RESEND_API_KEY)
    console.log('NEXT_PUBLIC_APP_URL:', process.env.NEXT_PUBLIC_APP_URL)

    // Test sending beta invite email
    try {
      const result = await sendBetaInvite(email, name, token)
      console.log('Email sent successfully:', result)
      
      return NextResponse.json({
        success: true,
        message: 'Test email sent successfully',
        result: result
      })
    } catch (emailError) {
      console.error('Email sending failed:', emailError)
      
      return NextResponse.json({
        success: false,
        error: 'Email sending failed',
        details: emailError instanceof Error ? emailError.message : String(emailError)
      }, { status: 500 })
    }

  } catch (error) {
    console.error('Test email endpoint error:', error)
    return NextResponse.json(
      { 
        error: 'Internal server error',
        details: error instanceof Error ? error.message : String(error)
      },
      { status: 500 }
    )
  }
}
