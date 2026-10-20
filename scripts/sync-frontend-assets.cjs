const fs = require('node:fs')
const os = require('node:os')
const path = require('node:path')

const projectDirectory = path.resolve(__dirname, '..')
const dependencyDirectory = path.join(projectDirectory, 'node_modules')
const publicDirectory = path.join(projectDirectory, 'public')
const targetDirectory = path.join(publicDirectory, 'vendor')

const assets = [
    ['bootstrap/dist/css/bootstrap.min.css', 'bootstrap/bootstrap.min.css'],
    ['bootstrap/dist/css/bootstrap.min.css.map', 'bootstrap/bootstrap.min.css.map'],
    ['bootstrap/dist/js/bootstrap.bundle.min.js', 'bootstrap/bootstrap.bundle.min.js'],
    ['bootstrap/dist/js/bootstrap.bundle.min.js.map', 'bootstrap/bootstrap.bundle.min.js.map'],
    ['bootstrap/LICENSE', 'licenses/bootstrap.txt'],
    ['bootstrap-icons/font/bootstrap-icons.css', 'bootstrap-icons/bootstrap-icons.css'],
    ['bootstrap-icons/font/fonts/bootstrap-icons.woff', 'bootstrap-icons/fonts/bootstrap-icons.woff'],
    ['bootstrap-icons/font/fonts/bootstrap-icons.woff2', 'bootstrap-icons/fonts/bootstrap-icons.woff2'],
    ['bootstrap-icons/LICENSE.md', 'licenses/bootstrap-icons.md'],
    ['vanillajs-datepicker/dist/css/datepicker.min.css', 'datepicker/datepicker.min.css'],
    ['vanillajs-datepicker/dist/js/datepicker-full.min.js', 'datepicker/datepicker-full.min.js'],
    ['vanillajs-datepicker/LICENSE', 'licenses/vanillajs-datepicker.txt'],
    ['marked/marked.min.js', 'marked/marked.min.js'],
    ['marked/LICENSE.md', 'licenses/marked.md'],
    ['qrcodejs/qrcode.min.js', 'qrcode/qrcode.min.js'],
    ['qrcodejs/LICENSE', 'licenses/qrcodejs.txt'],
    ['swagger-ui-dist/swagger-ui.css', 'swagger-ui/swagger-ui.css'],
    ['swagger-ui-dist/swagger-ui.css.map', 'swagger-ui/swagger-ui.css.map'],
    ['swagger-ui-dist/swagger-ui-bundle.js', 'swagger-ui/swagger-ui-bundle.js'],
    ['swagger-ui-dist/swagger-ui-bundle.js.map', 'swagger-ui/swagger-ui-bundle.js.map'],
    ['swagger-ui-dist/LICENSE', 'licenses/swagger-ui.txt'],
    ['swagger-ui-dist/NOTICE', 'licenses/swagger-ui-NOTICE.txt'],
]

function resolveWithin(baseDirectory, relativePath) {
    const absolutePath = path.resolve(baseDirectory, relativePath)
    const relativeResolvedPath = path.relative(baseDirectory, absolutePath)

    if (relativeResolvedPath === ''
        || relativeResolvedPath.startsWith(`..${path.sep}`)
        || path.isAbsolute(relativeResolvedPath)
    ) {
        throw new Error(`Path escapes its expected directory: ${relativePath}`)
    }

    return absolutePath
}

function copyAssets(destinationDirectory) {
    for (const [sourcePath, destinationPath] of assets) {
        const absoluteSourcePath = resolveWithin(dependencyDirectory, sourcePath)
        const absoluteDestinationPath = resolveWithin(destinationDirectory, destinationPath)

        if (fs.existsSync(absoluteSourcePath) === false) {
            throw new Error(`Frontend dependency asset does not exist: ${sourcePath}`)
        }

        fs.mkdirSync(path.dirname(absoluteDestinationPath), {recursive: true})
        if (destinationPath.startsWith('licenses/')) {
            const license = fs.readFileSync(absoluteSourcePath, 'utf8').replaceAll('\r\n', '\n')
            fs.writeFileSync(absoluteDestinationPath, license)
        } else {
            fs.copyFileSync(absoluteSourcePath, absoluteDestinationPath)
        }
    }
}

function listFiles(directory, prefix = '') {
    if (fs.existsSync(directory) === false) {
        return []
    }

    const files = []
    for (const entry of fs.readdirSync(directory, {withFileTypes: true})) {
        const relativePath = path.join(prefix, entry.name)
        const absolutePath = path.join(directory, entry.name)

        if (entry.isDirectory()) {
            files.push(...listFiles(absolutePath, relativePath))
        } else if (entry.isFile()) {
            files.push(relativePath)
        } else {
            throw new Error(`Unsupported generated asset entry: ${relativePath}`)
        }
    }

    return files.sort()
}

function compareDirectories(expectedDirectory, actualDirectory) {
    const expectedFiles = listFiles(expectedDirectory)
    const actualFiles = listFiles(actualDirectory)
    const expectedFileSet = new Set(expectedFiles)
    const actualFileSet = new Set(actualFiles)
    const differences = []

    for (const relativePath of expectedFiles) {
        if (actualFileSet.has(relativePath) === false) {
            differences.push(`missing: ${relativePath}`)
            continue
        }

        const expected = fs.readFileSync(path.join(expectedDirectory, relativePath))
        const actual = fs.readFileSync(path.join(actualDirectory, relativePath))
        if (expected.equals(actual) === false) {
            differences.push(`changed: ${relativePath}`)
        }
    }

    for (const relativePath of actualFiles) {
        if (expectedFileSet.has(relativePath) === false) {
            differences.push(`unexpected: ${relativePath}`)
        }
    }

    return differences
}

function checkAssets() {
    const temporaryDirectory = fs.mkdtempSync(path.join(os.tmpdir(), 'movary-frontend-assets-'))

    try {
        copyAssets(temporaryDirectory)
        const differences = compareDirectories(temporaryDirectory, targetDirectory)
        if (differences.length > 0) {
            throw new Error(`Committed frontend assets are not current:\n${differences.join('\n')}`)
        }
    } finally {
        fs.rmSync(temporaryDirectory, {recursive: true, force: true})
    }

    console.log(`Verified ${assets.length} frontend dependency assets.`)
}

function syncAssets() {
    const stagingDirectory = fs.mkdtempSync(path.join(publicDirectory, '.vendor-'))

    try {
        copyAssets(stagingDirectory)
        fs.rmSync(targetDirectory, {recursive: true, force: true})
        fs.renameSync(stagingDirectory, targetDirectory)
    } finally {
        fs.rmSync(stagingDirectory, {recursive: true, force: true})
    }

    console.log(`Synchronized ${assets.length} frontend dependency assets.`)
}

const cliArguments = process.argv.slice(2)
if (cliArguments.length > 1 || (cliArguments.length === 1 && cliArguments[0] !== '--check')) {
    console.error('Usage: node scripts/sync-frontend-assets.cjs [--check]')
    process.exitCode = 1
} else {
    try {
        if (cliArguments[0] === '--check') {
            checkAssets()
        } else {
            syncAssets()
        }
    } catch (error) {
        console.error(error.message)
        process.exitCode = 1
    }
}
