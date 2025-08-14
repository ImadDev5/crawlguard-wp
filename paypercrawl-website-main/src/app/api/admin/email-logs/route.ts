import { NextRequest, NextResponse } from 'next/server'
import { db } from '@/lib/db'

export async function GET(request: NextRequest) {
  try {
    // Check admin authentication
    const authHeader = request.headers.get('authorization')
    const adminKey = authHeader?.replace('Bearer ', '')
    
    if (adminKey !== process.env.ADMIN_API_KEY) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 })
    }

    // Get recent email logs
    const emailLogs = await db.emailLog.findMany({
      orderBy: { createdAt: 'desc' },
      take: 50,
      select: {
        id: true,
        to: true,
        subject: true,
        status: true,
        provider: true,
        createdAt: true,
        body: true
      }
    })

    return NextResponse.json({
      logs: emailLogs,
      total: emailLogs.length
    })

  } catch (error) {
    console.error('Error fetching email logs:', error)
    return NextResponse.json(
      { error: 'Failed to fetch email logs' },
      { status: 500 }
    )
  }
}
