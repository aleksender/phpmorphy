<?php
class test_Functional_Common extends PHPUnit_Framework_TestCase {
    /**
     * @var phpMorphy_MorphyInterface
     */
    protected $morphy;

    function setUp() {
        $this->morphy = new phpMorphy(
            null,
            'ru_RU'
        );
    }

    function testVerySimple() {
        //$this->assertLemma('мама', 'мама');
        $this->assertLemma('мыла', 'мыть', 'мыло');
        $this->assertLemma('раму', 'рама', 'рам');
    }

    function testPredictByKnownSuffix() {
        $this->assertLemma('кластера', 'кластер');
        $this->assertNotPredicted();
        $this->assertLemma('мегакластера', 'мегакластер');
        $this->assertPredictedByKnownSuffix();
    }

    function testPredictByKnownSuffixWithSuffix() {
        $this->assertLemma('наикрасивейшего', 'красивый');
        $this->assertNotPredicted();
        $this->assertLemma('пренаикрасивейшего', 'прекрасивый');
        $this->assertPredictedByKnownSuffix();
    }

    function testNotSplitParadigms() {
        $paradigms = $this->morphy->findWord($this->toMorphyEncoding('айда'));
        $this->assertEquals(2, count($paradigms));
    }

    function testFindWord() {
        $paradigms = $this->morphy->findWord($this->toMorphyEncoding('мыла'));
        $this->assertEquals(2, count($paradigms));

        foreach($paradigms as $paradigm) {
            $all_forms = $paradigm->getAllForms();
            foreach($paradigm as $wf) {
                $word = $wf->getWord();
                $this->assertTrue(in_array($word, $all_forms), "$word failed");
            }
        }

        foreach($paradigms as $paradigm) {
            $all_forms = $paradigm->getAllForms();
            for($i = 0; $i < count($paradigm); $i++) {
                $word = $paradigm->getWordForm($i)->getWord();
                $this->assertTrue(in_array($word, $all_forms), "$word failed");
            }
        }
    }

    function testFindWord_GetFoundWord() {
        $paradigm = $this->morphy->findWord($this->toMorphyEncoding('программе'));
        $this->assertEquals(1, count($paradigm));
        $paradigm = $paradigm[0];

        $this->assertEquals(2, count($paradigm->getFoundWordForm()));
    }

    /**
     * @return array
     */
    public function providerBasic()
    {
        return array(
            array(
                'КОТ',
                array('КОТ'),
                array(
                    'КОТ',
                    'КОТА',
                    'КОТУ',
                    'КОТОМ',
                    'КОТЕ',
                    'КОТЫ',
                    'КОТОВ',
                    'КОТАМ',
                    'КОТАМИ',
                    'КОТАХ'
                ),
                array('КОТ'),
            ),
            array(
                'СОБАКА',
                array('СОБАКА'),
                array(
                    'СОБАКА',
                    'СОБАКИ',
                    'СОБАКЕ',
                    'СОБАКУ',
                    'СОБАКОЙ',
                    'СОБАКОЮ',
                    'СОБАК',
                    'СОБАКАМ',
                    'СОБАКАМИ',
                    'СОБАКАХ'
                ),
                array('СОБАК'),
            ),
            array(
                'кот',
                false,
                false,
                false,
            ),
            array(
                array('кот', 'собака'),
                array('кот' => false, 'собака' => false),
                array('кот' => false, 'собака' => false),
                array('кот' => false, 'собака' => false),
            ),
            array(
                array('КОТ', 'СОБАКА'),
                array('КОТ' => array('КОТ'), 'СОБАКА' => array('СОБАКА')),
                array(
                    'КОТ' => array(
                        'КОТ',
                        'КОТА',
                        'КОТУ',
                        'КОТОМ',
                        'КОТЕ',
                        'КОТЫ',
                        'КОТОВ',
                        'КОТАМ',
                        'КОТАМИ',
                        'КОТАХ'
                    ),
                    'СОБАКА' => array(
                        'СОБАКА',
                        'СОБАКИ',
                        'СОБАКЕ',
                        'СОБАКУ',
                        'СОБАКОЙ',
                        'СОБАКОЮ',
                        'СОБАК',
                        'СОБАКАМ',
                        'СОБАКАМИ',
                        'СОБАКАХ'
                    )
                ),
                array('КОТ' => array('КОТ'), 'СОБАКА' => array('СОБАК')),
            )
        );
    }

    /**
     * @dataProvider providerBasic
     * @param string|array $word
     * @param mixed $expectedBaseForm
     * @param mixed $expectedAllForms
     * @param mixed $expectedPseudoRoot
     */
    public function testBasic($word, $expectedBaseForm, $expectedAllForms, $expectedPseudoRoot)
    {
        $baseForm = $this->morphy->getBaseForm($word);
        $this->assertEquals($expectedBaseForm, $baseForm);

	    $allForms = $this->morphy->getAllForms($word);
        $this->assertEquals($expectedAllForms, $allForms);

	    $pseudoRoot = $this->morphy->getPseudoRoot($word);
        $this->assertEquals($expectedPseudoRoot, $pseudoRoot);
    }

    protected function toMorphyEncoding($string) {
        return mb_strtoupper($string, 'utf-8');
    }

    protected function assertLemma($lemma) {
        $expected = func_get_args();
        array_shift($expected);

        $this->assertEqualsArrays(
            $expected,
            $this->morphy->lemmatize($this->toMorphyEncoding($lemma))
        );
    }

    protected function assertEqualsArrays($expected, $actual) {
        $this->normalizeArray($expected);
        $this->normalizeArray($actual);

        $msg = "Morphy returns " .
               (false === $actual ? 'FALSE' : implode(', ', $actual)) . ' but ' .
               (false === $expected ? 'FALSE' : implode(', ', $expected)) . ' expected';
        
        $this->assertEquals($expected, $actual, $msg);
    }

    protected function normalizeArray(&$array) {
        if(false !== $array) {
            $old_encoding = mb_internal_encoding();
            mb_internal_encoding('utf-8');

            $array = array_map('mb_strtoupper', array_values((array)$array));
            sort($array);
            
            mb_internal_encoding($old_encoding);
        }
    }

    protected function assertNotPredicted() {
        $this->assertFalse($this->morphy->isLastPredicted(), "Expect for word exists in dictionary");
    }

    protected function assertPredictedByKnownSuffix() {
        $this->assertEquals(
            phpMorphy::PREDICT_BY_SUFFIX,
            $this->morphy->getLastPredictionType(),
            "Expect for prediction by known suffix"
        );
    }

    protected function assertPredictedBySuffix() {
        $this->assertEquals(
            phpMorphy::PREDICT_BY_DB,
            $this->morphy->getLastPredictionType(),
            "Expect for prediction by suffix"
        );
    }
}
