const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        const fullPath = path.join(dir, file);
        const stat = fs.statSync(fullPath);
        if (stat && stat.isDirectory()) {
            results = results.concat(walk(fullPath));
        } else if (file.endsWith('.blade.php')) {
            results.push(fullPath);
        }
    });
    return results;
}

const viewsDir = path.resolve(__dirname, '../resources/views');
const files = walk(viewsDir);

const violations = {
    scriptTags: [],
    inlineEvents: [],
    styleTags: [],
    styleAttrs: []
};

// Event handlers pattern: onclick=, onchange=, etc.
const eventHandlerRegex = /\bon(click|change|submit|input|keydown|keyup|keypress|mouseover|mouseout|mouseenter|mouseleave|focus|blur|load|error)\s*=/i;
const styleAttrRegex = /\bstyle\s*=\s*["'][^"']*["']/i;

files.forEach(f => {
    const content = fs.readFileSync(f, 'utf8');
    const lines = content.split(/\r?\n/);
    
    lines.forEach((line, idx) => {
        const lineNum = idx + 1;
        const relativePath = path.relative(path.resolve(__dirname, '..'), f).replace(/\\/g, '/');

        // Check <script> tag (ignoring ld+json if any)
        if (/<script\b/i.test(line) && !/type\s*=\s*["']application\/ld\+json["']/i.test(line)) {
            violations.scriptTags.push({ file: relativePath, line: lineNum, text: line.trim() });
        }

        // Check <style> tag
        if (/<style\b/i.test(line)) {
            violations.styleTags.push({ file: relativePath, line: lineNum, text: line.trim() });
        }

        // Check inline event handlers
        if (eventHandlerRegex.test(line)) {
            violations.inlineEvents.push({ file: relativePath, line: lineNum, text: line.trim() });
        }

        // Check inline style= attributes
        if (styleAttrRegex.test(line)) {
            violations.styleAttrs.push({ file: relativePath, line: lineNum, text: line.trim() });
        }
    });
});

console.log('====================================================');
console.log('     Kayan ERP — Blade Separation Audit Report      ');
console.log('====================================================');
console.log(`Total Blade files inspected: ${files.length}`);
console.log(`Inline <script> tags found:  ${violations.scriptTags.length}`);
console.log(`Inline event handlers found: ${violations.inlineEvents.length}`);
console.log(`Inline <style> tags found:   ${violations.styleTags.length}`);
console.log(`Inline style="..." found:    ${violations.styleAttrs.length}`);
console.log('----------------------------------------------------');

if (violations.scriptTags.length > 0) {
    console.log('\n❌ INLINE <script> VIOLATIONS:');
    violations.scriptTags.forEach(v => console.log(`   ${v.file}:${v.line} -> ${v.text.substring(0, 80)}`));
}

if (violations.inlineEvents.length > 0) {
    console.log('\n❌ INLINE EVENT HANDLER VIOLATIONS:');
    violations.inlineEvents.forEach(v => console.log(`   ${v.file}:${v.line} -> ${v.text.substring(0, 80)}`));
}

if (violations.styleTags.length > 0) {
    console.log('\n❌ INLINE <style> VIOLATIONS:');
    violations.styleTags.forEach(v => console.log(`   ${v.file}:${v.line} -> ${v.text.substring(0, 80)}`));
}

if (violations.styleAttrs.length > 0) {
    console.log('\n❌ INLINE style="..." ATTRIBUTE SUMMARY:');
    const fileMap = {};
    violations.styleAttrs.forEach(v => {
        fileMap[v.file] = (fileMap[v.file] || 0) + 1;
    });
    Object.entries(fileMap).forEach(([f, count]) => {
        console.log(`   ${f}: ${count} violation(s)`);
    });
}

const totalViolations = violations.scriptTags.length +
    violations.inlineEvents.length +
    violations.styleTags.length +
    violations.styleAttrs.length;

console.log('====================================================');
if (totalViolations > 0) {
    console.log(`❌ AUDIT FAILED with ${totalViolations} separation violation(s).`);
    process.exit(1);
} else {
    console.log('✅ AUDIT PASSED: Zero inline JS and zero inline CSS detected.');
    process.exit(0);
}
