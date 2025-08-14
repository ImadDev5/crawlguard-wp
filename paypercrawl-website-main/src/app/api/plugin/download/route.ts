import { NextRequest, NextResponse } from 'next/server';
import fs from 'fs';
import path from 'path';
import archiver from 'archiver';

// Cache the zip file in memory to speed up downloads
let cachedZip: Buffer | null = null;
let cacheTime: number = 0;
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes cache

// Force clear cache on module load to ensure latest changes
cachedZip = null;
cacheTime = 0;

async function createZipArchive(): Promise<Buffer> {
  const pluginDir = path.join(process.cwd(), 'crawlguard-wp-main');
  
  // Create a zip archive of the plugin with lower compression for speed
  const archive = archiver('zip', {
    zlib: { level: 6 } // Balanced compression (faster than 9)
  });

  const chunks: Buffer[] = [];
  
  // Create a promise to handle the archive stream
  const archivePromise = new Promise<Buffer>((resolve, reject) => {
    archive.on('data', (chunk) => chunks.push(chunk));
    archive.on('end', () => resolve(Buffer.concat(chunks)));
    archive.on('error', reject);
  });

  // Exclude unnecessary files for faster zipping
  archive.glob('**/*', {
    cwd: pluginDir,
    ignore: ['node_modules/**', '*.log', '.git/**', '*.zip', 'tests/**', 'docs/**']
  }, { prefix: 'crawlguard-wp/' });
  
  // Finalize the archive
  archive.finalize();

  // Wait for the archive to complete
  return await archivePromise;
}

export async function GET(request: NextRequest) {
  try {
    const pluginDir = path.join(process.cwd(), 'crawlguard-wp-main');
    
    // Check if plugin directory exists
    if (!fs.existsSync(pluginDir)) {
      return NextResponse.json(
        { error: 'Plugin not found' },
        { status: 404 }
      );
    }

    // Check if we have a valid cached version
    const now = Date.now();
    if (!cachedZip || (now - cacheTime) > CACHE_DURATION) {
      // Create new zip and cache it
      cachedZip = await createZipArchive();
      cacheTime = now;
    }

    // Return the cached zip file as a download
    return new NextResponse(cachedZip as any, {
      headers: {
        'Content-Type': 'application/zip',
        'Content-Disposition': 'attachment; filename="crawlguard-wp-paypercrawl.zip"',
        'Content-Length': cachedZip.length.toString(),
        'Cache-Control': 'public, max-age=300', // Browser cache for 5 minutes
      },
    });
  } catch (error) {
    console.error('Error creating plugin download:', error);
    return NextResponse.json(
      { error: 'Failed to create plugin download' },
      { status: 500 }
    );
  }
}
