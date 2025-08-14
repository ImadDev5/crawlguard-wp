import { NextRequest, NextResponse } from 'next/server'
import { db } from '@/lib/db'

function verifyAdminAuth(request: NextRequest): boolean {
  const authHeader = request.headers.get('authorization')
  const adminKey = authHeader?.replace('Bearer ', '')
  return adminKey === process.env.ADMIN_API_KEY
}

export async function GET(request: NextRequest) {
  try {
    if (!verifyAdminAuth(request)) {
      return NextResponse.json(
        { error: 'Unauthorized' },
        { status: 401 }
      )
    }

    const { searchParams } = new URL(request.url)
    const status = searchParams.get('status')
    const page = parseInt(searchParams.get('page') || '1')
    const limit = parseInt(searchParams.get('limit') || '10')
    const skip = (page - 1) * limit

    const where = status ? { status } : {}

    const [contactSubmissions, total] = await Promise.all([
      db.contactSubmission.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        skip,
        take: limit,
      }),
      db.contactSubmission.count({ where })
    ])

    return NextResponse.json({
      contactSubmissions,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    })
  } catch (error) {
    console.error('Error fetching contact submissions:', error)
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    )
  }
}

export async function PATCH(request: NextRequest) {
  try {
    if (!verifyAdminAuth(request)) {
      return NextResponse.json(
        { error: 'Unauthorized' },
        { status: 401 }
      )
    }

    const body = await request.json()
    const { contactId, status } = body

    const contact = await db.contactSubmission.update({
      where: { id: contactId },
      data: {
        status: status || undefined,
        updatedAt: new Date()
      }
    })

    return NextResponse.json(contact)
  } catch (error) {
    console.error('Error updating contact submission:', error)
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    )
  }
}
