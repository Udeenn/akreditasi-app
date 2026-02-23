<?php
$file = __DIR__ . '/app/Http/Controllers/VisitHistory.php';
$lines = file($file);

// Find the end of new function (line "    }" after the new function's catch block around line 215-216)
// Then find "private function buildQuery" line
// Delete everything in between

$deleteStart = null;
$deleteEnd = null;

for ($i = 0; $i < count($lines); $i++) {
    $line = trim($lines[$i]);
    
    // Find the first dangling line after the new function ends
    // The new function ends with "    }" at around line 216
    // Then there's dangling old code before "private function buildQuery"
    if ($deleteStart === null && $i > 215 && (
        str_contains($lines[$i], '$listFakultas') ||
        str_contains($lines[$i], '$prodiToFacultyMap') ||  
        str_contains($lines[$i], '$defaultTglAwal') ||
        str_contains($lines[$i], 'buildQuery($request') ||
        str_contains($lines[$i], '$dataCollection') ||
        str_contains($lines[$i], 'OPTIMIZATION') ||
        str_contains($lines[$i], '$aggregatedData') ||
        str_contains($lines[$i], '$finalCollection') ||
        str_contains($lines[$i], 'Cache::remember($viewCacheKey') ||
        str_contains($lines[$i], '$cachedView') ||
        str_contains($lines[$i], "catch (\\Throwable") ||
        str_contains($lines[$i], "return back()->with('error'") ||
        str_contains($lines[$i], '$blacklist') ||
        str_contains($lines[$i], '$lokasiMapping') ||
        str_contains($lines[$i], "compact(")
    )) {
        $deleteStart = $i;
    }
    
    if (str_contains($lines[$i], 'private function buildQuery')) {
        $deleteEnd = $i;
        break;
    }
}

echo "Total lines: " . count($lines) . PHP_EOL;
echo "Delete from line " . ($deleteStart + 1) . " to " . ($deleteEnd) . PHP_EOL;

if ($deleteStart !== null && $deleteEnd !== null) {
    // Remove the dangling lines
    $before = array_slice($lines, 0, $deleteStart);
    $after = array_slice($lines, $deleteEnd);
    $newContent = implode('', $before) . "\n" . implode('', $after);
    file_put_contents($file, $newContent);
    echo "Done! Removed " . ($deleteEnd - $deleteStart) . " lines." . PHP_EOL;
    echo "New total: " . count(file($file)) . PHP_EOL;
} else {
    echo "Could not find boundaries. deleteStart=$deleteStart deleteEnd=$deleteEnd" . PHP_EOL;
}
