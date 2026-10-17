// Screenshots of Dog-ear Books, as used in Social Images for Craft's docs.
// Run against the demo with its content and images made:
//
//   php craft demo/content && php craft social-images/generate
//   node scripts/screenshots.mjs
//
// DEMO_URL     the demo's address (default http://social-images-craft-demo.test)
// SCREENSHOTS  where to write them (default ../social-images-craft/docs/images)
// CHROME       a Chrome or Chromium to drive (default: Google Chrome on macOS)
// CRAFT_USER, CRAFT_PASSWORD  the local admin you made with `craft install`
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import puppeteer from 'puppeteer';

const base = (process.env.DEMO_URL || 'http://social-images-craft-demo.test').replace(/\/$/, '');
const out = process.env.SCREENSHOTS || new URL('../../social-images-craft/docs/images', import.meta.url).pathname;
const chrome = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

mkdirSync(out, { recursive: true });

const browser = await puppeteer.launch({ executablePath: chrome, args: ['--ignore-certificate-errors'] });
const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });
await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }, { name: 'prefers-reduced-motion', value: 'reduce' }]);

const settle = (ms = 1200) => new Promise((resolve) => setTimeout(resolve, ms));

// A local demo runs Craft Pro and SEOmatic as trials, which say so at the
// foot of every page, and dev mode marks the page. Neither is the plugin.
async function tidy() {
    await page.addStyleTag({ content: '#notifications, .notification, #global-footer, #trial-info, #devmode { display: none !important; }' });
    await settle(300);
}

// Sign in as the local admin.
await page.goto(`${base}/admin/login`, { waitUntil: 'networkidle0' });
await page.waitForSelector('.login-username');
await page.type('.login-username', process.env.CRAFT_USER || 'admin');
await page.type('.login-password', process.env.CRAFT_PASSWORD || 'password');
await Promise.all([page.waitForNavigation(), page.keyboard.press('Enter')]);

// The designer, with a journal post and the title selected.
await page.goto(`${base}/admin/social-images/templates/editorial`, { waitUntil: 'networkidle0' });
await tidy();
const entry = await page.$eval('#si-entry', (select) => [...select.options].find((o) => o.text.startsWith('In conversation'))?.value);
await page.select('#si-entry', entry);
await settle(1500);
await page.evaluate(() => [...document.querySelectorAll('.si-layers__row')].find((row) => row.textContent.includes('Title'))?.click());
await settle(800);
await page.screenshot({ path: `${out}/designer.png` });

// Every entry's images.
await page.goto(`${base}/admin/social-images/images`, { waitUntil: 'networkidle0' });
await tidy();
await settle(1500);
await page.screenshot({ path: `${out}/images.png` });

// The templates.
await page.goto(`${base}/admin/social-images`, { waitUntil: 'networkidle0' });
await tidy();
await settle(2000);
await page.screenshot({ path: `${out}/templates.png` });

// A journal post, with its Social image field.
const id = execFileSync('php', ['craft', 'demo/content/id', 'journal', 'in-conversation-with-imogen-hale'], { cwd: new URL('..', import.meta.url).pathname }).toString().trim();
await page.goto(`${base}/admin/entries/journal/${id}`, { waitUntil: 'networkidle0' });
await tidy();
await settle(2500);
await page.evaluate(() => document.querySelector('.si-field')?.scrollIntoView({ block: 'center' }));
await settle(800);
await page.screenshot({ path: `${out}/entry.png` });

await browser.close();
console.log(`Written to ${out}`);
