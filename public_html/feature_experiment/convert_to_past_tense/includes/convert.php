<?php

function transformSpecificPhrases($phrase) {
    // Auxiliary transformation rules for specific phrases
    $phraseTransformations = [
        'will be submitted' => 'was submitted',
        'Prepare and submit' => 'Prepared and submitted',
        'evaluation' => 'evaluated',
    ];

    // Apply each transformation rule to the entire text
    foreach ($phraseTransformations as $search => $replace) {
        $phrase = str_replace($search, $replace, $phrase);
    }

    return $phrase;
}

// Test the function
$complexPhrase = "100% (all 2 programs) Prepare and submit 100% of the required documents for ISO (2 Internal Audit and 2 MR) evaluation with no mistakes or deficiencies in the preparation will be submitted to the VPAA, QAM, and OP, 1 month before the audit.";
$transformedPhrase = transformSpecificPhrases($complexPhrase);

echo "Original: $complexPhrase\n";
echo "Transformed: $transformedPhrase\n";
?>
