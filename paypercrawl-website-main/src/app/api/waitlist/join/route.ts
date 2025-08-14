import { NextRequest, NextResponse } from 'next/server'
import { db } from '@/lib/db'
import { z } from 'zod'
import { generateInviteToken } from '@/lib/utils'
import { sendWaitlistConfirmation } from '@/lib/email'

const waitlistSchema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters'),
  email: z.string().email('Invalid email address'),
  website: z.string().url().optional().or(z.literal('')),
  companySize: z.enum(['small', 'medium', 'large']).optional(),
  useCase: z.string().optional(),
})

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const validatedData = waitlistSchema.parse(body)
    
    // Check if already on waitlist
    const existingEntry = await db.waitlistEntry.findUnique({
      where: { email: validatedData.email }
    })
    
    if (existingEntry) {
      return NextResponse.json(
        { error: 'This email is already on the waitlist' },
        { status: 400 }
      )
    }
    
    // Create waitlist entry
    const waitlistEntry = await db.waitlistEntry.create({
      data: {
        name: validatedData.name,
        email: validatedData.email,
        website: validatedData.website || null,
        companySize: validatedData.companySize || null,
        useCase: validatedData.useCase || null,
        inviteToken: generateInviteToken(), // Generate unique token
      }
    })
    
    // Get waitlist position
    const position = await getWaitlistPosition(validatedData.email)

    // Send confirmation email
    try {
      await sendWaitlistConfirmation(validatedData.email, validatedData.name, position)
    } catch (emailError) {
      console.error('Failed to send confirmation email:', emailError)
      // Continue with success response even if email fails
    }
    
    return NextResponse.json({
      message: 'Successfully joined waitlist',
      position: position
    })
    
  } catch (error) {
    console.error('Waitlist join error:', error)
    
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

async function getWaitlistPosition(email: string): Promise<number> {
  const entry = await db.waitlistEntry.findUnique({
    where: { email }
  })
  
  if (!entry) return 0
  
  const count = await db.waitlistEntry.count({
    where: {
      createdAt: {
        lte: entry.createdAt
      }
    }
  })
  return count
}

export async function GET() {
  try {
    const waitlist = await db.waitlistEntry.findMany({
      orderBy: { createdAt: 'desc' }
    })
    
    return NextResponse.json(waitlist)
  } catch (error) {
    console.error('Error fetching waitlist:', error)
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    )
  }
}
