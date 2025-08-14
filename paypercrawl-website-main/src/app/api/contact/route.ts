import { NextRequest, NextResponse } from 'next/server'
import { db } from '@/lib/db'
import { sendContactNotification } from '@/lib/email'
import { z } from 'zod'

const contactSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  email: z.string().email('Invalid email address'),
  subject: z.string().optional(),
  message: z.string().min(10, 'Message must be at least 10 characters'),
})

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const validatedData = contactSchema.parse(body)
    
    // Save to database
    const contactSubmission = await db.contactSubmission.create({
      data: {
        name: validatedData.name,
        email: validatedData.email,
        subject: validatedData.subject || 'General Inquiry',
        message: validatedData.message,
      }
    })
    
    // Send notification email
    try {
      await sendContactNotification(
        validatedData.name,
        validatedData.email,
        validatedData.subject || 'General Inquiry',
        validatedData.message
      )
    } catch (emailError) {
      console.error('Failed to send contact notification email:', emailError)
      // Don't fail the request if email fails
    }
    
    return NextResponse.json({
      message: 'Contact form submitted successfully',
      submissionId: contactSubmission.id
    })
    
  } catch (error) {
    console.error('Contact form submission error:', error)
    
    if (error instanceof z.ZodError) {
      return NextResponse.json(
        { error: 'Validation failed', details: error.issues },
        { status: 400 }
      )
    }
    
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    )
  }
}
