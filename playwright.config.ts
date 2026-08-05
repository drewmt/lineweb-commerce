import path from 'node:path';

import { defineConfig } from '@playwright/test';

const baseURL = process.env.WP_BASE_URL || 'http://localhost:8888';
const artifactsPath =
	process.env.WP_ARTIFACTS_PATH ||
	path.join( process.cwd(), 'artifacts-commerce' );
const storageStatePath =
	process.env.STORAGE_STATE_PATH ||
	path.join( artifactsPath, 'storage-states/admin.json' );

process.env.WP_BASE_URL = baseURL;
process.env.WP_ARTIFACTS_PATH = artifactsPath;
process.env.STORAGE_STATE_PATH = storageStatePath;

export default defineConfig( {
	testDir: './specs',
	outputDir: path.join( artifactsPath, 'test-results' ),
	fullyParallel: false,
	workers: 1,
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI ? 'github' : 'list',
	timeout: 100_000,
	globalSetup: path.join(
		__dirname,
		'node_modules/@wordpress/scripts/config/playwright/global-setup.js'
	),
	use: {
		baseURL,
		headless: true,
		storageState: storageStatePath,
		trace: 'on-first-retry',
		viewport: { width: 1440, height: 1000 },
	},
} );
