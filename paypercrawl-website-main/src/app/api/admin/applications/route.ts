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

    const [applications, total] = await Promise.all([
      db.betaApplication.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        skip,
        take: limit,
      }),
      db.betaApplication.count({ where })
    ])

    return NextResponse.json({
      applications,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    })
  } catch (error) {
    console.error('Error fetching applications:', error)
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
    const { applicationId, status, notes } = body

    const application = await db.betaApplication.update({
      where: { id: applicationId },
      data: {
        status: status || undefined,
        notes: notes || undefined,
        updatedAt: new Date()
      }
    })

    return NextResponse.json(application)
  } catch (error) {
    console.error('Error updating application:', error)
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    )
  }
}
