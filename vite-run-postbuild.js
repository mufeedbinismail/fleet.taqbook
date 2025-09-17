import fs from 'fs-extra';
import path from 'path';

const sourceDir = path.resolve(__dirname, 'public/build');
const destDir = path.resolve(__dirname, 'public/themes/cume');
const manifestName = 'manifest.json';
const manifestPath = path.join(sourceDir, manifestName);

// Ensure the destination directory exists
fs.ensureDirSync(destDir);

export default function postBuildPlugin() {
    return {
        name: 'post-build-plugin',
        closeBundle() {
            // As the file names are hashed, you'll need to find them.
            // Let's assume you have a manifest file.
            const manifest = fs.readJsonSync(manifestPath);
            const themeCssFile = manifest["resources/css/fa.css"].file;
            fs.copySync(path.join(sourceDir, themeCssFile), path.join(destDir, "default.css"));
            fs.copySync(manifestPath, path.join(destDir, manifestName));

            console.log('Theme files moved successfully!');
        }
    };
}