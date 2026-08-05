import { chromium } from '@playwright/test';
import { dirname, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const toolsDirectory = dirname( fileURLToPath( import.meta.url ) );
const assetDirectory = join( toolsDirectory, 'demo-assets' );
const browser = await chromium.launch( { headless: true } );
const page = await browser.newPage( {
	viewport: { width: 1200, height: 900 },
} );

for ( const name of [ 'starter', 'growth', 'scale' ] ) {
	await page.goto(
		pathToFileURL( join( assetDirectory, `${ name }.svg` ) ).href
	);
	await page.screenshot( {
		path: join( assetDirectory, `${ name }.png` ),
		clip: { x: 0, y: 0, width: 1200, height: 900 },
	} );
}

await browser.close();
