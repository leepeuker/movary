import { defineConfig, splitVendorChunkPlugin } from "vite";
import { glob } from 'glob';
import { nodePolyfills } from 'vite-plugin-node-polyfills'
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import liveReload from "vite-plugin-live-reload";
import fs from 'fs';

const serverHost = process.env.VITE_SERVER_HOST ?? 'localhost';
const serverPort = process.env.VITE_SERVER_PORT ?? 5173;
const outputDir = __dirname + '/public/build';

export default defineConfig({
    plugins: [
        liveReload([
            __dirname + '/public/**/*.(css|js|)',
            __dirname + '/templates/**/*.twig'
        ], {
            root: __dirname
        }),
        splitVendorChunkPlugin(),
        nodePolyfills({
            include: ['path', 'url']
        }),
    ],
    
    root: 'public',
    resolve: {
        alias: {
            '@': 'public'
        }
    },
    base: '/',
    build: {
        outDir: outputDir,
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: Object.fromEntries(
                glob.sync('public/**/*.js', { ignore: '**/build/**' }).map(file => [
                    path.relative(
                        'public',
                        file.slice(0, file.length - path.extname(file).length)
                    ),
                    fileURLToPath(new URL(file, import.meta.url))
                ])
            ),
            output: {
                dir: outputDir,
                entryFileNames: '[name]-[hash].js',
                assetFileNames: '[name]-[hash][extname]',
                manualChunks(id) {
                    if(id.includes('node_modules')) {
                        if(id.includes('bootstrap-datepicker')) {
                            return 'vendor-datepicker';
                        } else if(id.includes('bootstrap')) {
                            return 'vendor-bootstrap';
                        } else if(id.includes('swagger')) {
                            return 'vendor-swagger';
                        } else if(id.includes('marked')) {
                            return 'vendor-marked';
                        } else if(id.includes('jquery')) {
                            return 'vendor-jquery'
                        } else if(id.includes('lodash')) {
                            return 'vendor-lodash';
                        } else if(id.includes('immutable')) {
                            return 'vendor-immutable';
                        } else if(id.includes('autolinker')) {
                            return 'vendor-autolinker';
                        }
                        return 'vendor';
                    }
                }
            }
        }
    },
    
    server: {
        strictPort: true,
        port: serverPort,
        host: serverHost,
        origin: 'http://' + serverHost + ':' + serverPort,
    },
});