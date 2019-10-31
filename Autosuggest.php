<?php

/*
* This class gets the most probable completions of the beginning of a given word
* @param filename: string, typically a text file (.txt) containing the words to be used as reference
* @param begining: string, the starting letter(s) of the word
* Author: Krishna Guragai <krishna@gurkhatech.com>
* (C) Copyright 2019
*/

class Autosuggest
{
  private $words; // stores the prepared words from word source
    private $wordFreqs; // the frequecies of words in the words source e.g {'can'=>1, 'eat'=>3}
    private $lwl; // the length of the longest word
    private $dict; // a dictionary data structures to track the analysis of word source
    public $suggestions; // gets the probable completions

    private function readInWords($textfile)
    {
        /**
        * Gets unique words from the word source with more than one chars and stripes off unwanted chars
        */
        $file = "words.txt";
        if(is_file($textfile))
            $file = file_get_contents($textfile);

        $words = explode(' ', strtolower(trim(preg_replace('/\s\s+/', ' ', $file))));

        $fwords = array();

        foreach ($words as $key => $word)
        {
            if(strlen($word)>1)
            {
                $wd = preg_replace('/\'[^.]+$/', '', preg_replace('/\./', '', $word));
                //$wd = preg_replace('/[^a-z\-]/', '', $wd);
                $fwords[] = $wd;
            }
        }

        #TODO: 'smartly' filter out all special chars and numbers
        # such as ix, ~!@#$%^&*()_+{}|"0123 e.t.c

        // get word frequecies
        $this->wordFreqs = array_count_values($fwords);
        return array_unique($fwords);
       
    }

    private function longestWordLength()
    {

        /*
        * Gets the length of the longest word in the word source
        */
        $words = $this->wordFreqs;
        $max_length = -1;

        foreach ($words as $word_length)
        {
            if($word_length > $max_length)
                $max_length = $word_length;

        }
        return $max_length;
    }

    private function getWordDict($textfile)
    {

        /**
        * Prepares and fills a data structure/'dictionary' that contains the words
        */

        $k = 0;
        $wordDict = array();
        $elements = array();

        // 1. set up an array (declared as $wordDict)
        // with a length same as the length of the longest word in the word source;
        // each element of this array ($wordDict) is an array (declared as $elements);
        // the keys of each of its elements ($elements) are the letters from 'A' to 'Z'
        // the values of each entries(e.g $elements['A']) of each element($elements) is an array/entries of words

        while ($k < $this->lwl)
        {
            foreach (range('क', 'ज्ञ') as $letter)
            {
                $elements[$letter] = array();
            }
            $wordDict[$k] = $elements;
            $k++;
        }

        //2. fill the (dict) array with words from the word source such that;
        // a word that has the letter 'L' at its Xth position is added to the entry with key 'L'
        // of the element (i.e dict['x-1']['L'] array) at the Xth position(index x - 1 ) of the (dict) array

        //examples
        // a word that has the letter 'a' at its FIRST position is added to the entry with key 'A'
        // of the element(i.e dict[0]['A'] array) at the FIRST position(index 0) of the (dict) array

        // a word that has the letter 'a' at its SECOND position is added to the entry with key 'A'
        // of the element(i.e dict[1]['A'] array) at the SECOND position(index 1) of the array(dict)

        // a word that has the letter 'b' at its FIRST position is added to the entry with key 'B'
        // of the element(i.e dict[0]['B'] array) at the FIRST position(index 0) of the array(dict)

        // a word that has the letter 'b' at its SECOND position is added to the element with key 'B'
        // of the element(i.e dict[1]['B'] array) at the SECOND position(index 1) of the array(dict)
        // etc


        foreach ($this->words as $wordkey => $word)
        {
            $letters = str_split($word);
            $j = 0;
            while ($j < count($letters))
            {
                foreach ($letters as $key => $letter)
                {
                    if($j == $key )
                    {
                        $wordDict[$j][strtoupper($letter)][] = $word;
                    }
                }
                $j++;
            }
        }

        return $wordDict;
    }

    private function percentageFreqs($words)
    {
        /**
        * Gets the percentages of frequencies of words
        *
        */

        $totalWords = 0;
        $percentages = array();
        // calculate total number of words wanted
        foreach ($words as $word)
        {
            $totalWords += $this->wordFreqs[$word];
        }
        if(!empty($words)){
        	//calculate their percentages
	        foreach ($words as $word)
	        {
	            $freq = $this->wordFreqs[$word];
	            $percentages[$word] = $freq / $totalWords * 100;
	        }
    	}

        return $percentages;

    }

    private function getSuggestions($typedword)
    {
        /*
        * Return the possible completions of the typed word
        */

        $letters = str_split(strtoupper($typedword));
        $k = 0;

        $a1 = array();

        $a2 = array();

        // taking two arrays of words at a time;
        // intersect entries of all elements of the array (dict)
        // with key (or at index) same as the position of each letter for
        // all letters in the word to be completed ($typedword)

        // example: incase of $typedword = 'fruit';
        // a.) intersect dict[0]['F'] and dict[1]['R'], then;
        // b.) intersect result of a.) and dict[2]['U'] then;
        // c.) intersect result of b.) and dict[3]['I'] then;
        // d.) intersect result of c.) and dict[4]['T'],
        // doing so will provide an array of words starting with 'fruit' e.g 'fruits', 'fruitless' etc

        while($k < count($letters))
        {
            $a1 = $this->dict[$k][$letters[$k]];
            if(count($letters) == 1)
            {
                $a2 = $a1;
            }
            elseif($k == 0 and count($letters) > 1)
            {
                $a2 = $this->dict[$k + 1][$letters[$k + 1]];
            }
            else
            {
                $a2 = $a1;
                $a1 = $result;
            }

            $result = array_intersect($a1, $a2);
            $k += 1;
        }

        return $result;
    }

    public function doCompletion($begining)
    {

        // get suggestions
        $sgs = $this->getSuggestions($begining);
        // get their percentages
        $completions = $this->percentageFreqs($sgs);
        // sort in terms of percentages
        arsort($completions);
        // pick out upto top 10
        $this->suggestions = array_slice($completions, 0, 10, true);

    }


    public function __construct($filename)
    {
        // load words
        $this->words = $this->readInWords($filename);
        // get longest word length
        $this->lwl = $this->longestWordLength();
        // load the word dictionary
        $this->dict = $this->getWordDict($filename);

    }
}
