import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { glob } from 'glob';
console.log(Object.fromEntries(
    glob.sync('./templates/**/*.twig').map(file => [
        path.relative(
            'public',
            file.slice(0, file.length - path.extname(file).length)
        ),
        fileURLToPath(new URL(file, import.meta.url))
    ])
));