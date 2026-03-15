<?php
function transliterateRussianToEnglish($russianText) {
    $transliterationMap = [
        'ё' => 'yo',
        'ю' => 'yu',
        'ж' => 'zh',
        'ш' => 'sh',
        'щ' => 'sch',
        'ц' => 'cz',
        'ч' => 'ch',
        'я' => 'ja',
        'ь' => '',
        'ъ' => '',
        ' ' => '_',
    ];
    
    
    // Добавляем остальные буквы русского алфавита
    $russianAlphabet = 'абвгдезийклмнопрстуфхыэ';
    $englishAlphabet = 'abvgdezijklmnoprstufhye';
    
    for ($i = 0; $i < mb_strlen($russianAlphabet); $i++) {
        $russianChar = mb_substr($russianAlphabet, $i, 1);
        $englishChar = $englishAlphabet[$i];
        $transliterationMap[$russianChar] = $englishChar;
    }
    
    // Добавляем заглавные буквы
    $upperCaseMap = [];
    foreach ($transliterationMap as $russian => $english) {
        if ($russian !== ' ') {
            $upperCaseMap[mb_strtoupper($russian)] = ucfirst($english);
        }
    }
    
    $transliterationMap = array_merge($transliterationMap, $upperCaseMap);
    
    // Заменяем все символы согласно карте
    $result = strtr($russianText, $transliterationMap);
    
    // Удаляем все специальные символы (кроме подчеркивания)
    $result = preg_replace('/[^a-z0-9_]/', '', strtolower($result));
    
    // Удаляем множественные подчеркивания
    $result = preg_replace('/_+/', '_', $result);
    
    // Удаляем подчеркивания в начале и конце
    $result = trim($result, '_');
    
    return $result;
}