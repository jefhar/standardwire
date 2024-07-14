<?php namespace ProcessWire;

/**
 * PagesPathFinder Tests
 * 
 * Usage:
 * ~~~~~
 * $tester = $pages->pathFinder()->tester();
 * $a = $tester->testPath('/path/to/page/'); 
 * $a = $tester->testPage(Page $page);
 * $a = $tester->testPages("has_parent!=2");
 * $a = $tester->testPages(PageArray $items); 
 * ~~~~~
 * 
 */
class PagesPathFinderTests extends Wire {

	/**
	 * @return PagesPathFinder
	 * 
	 */
	public function pathFinder() {
		return $this->wire()->pages->pathFinder();
	}
	
	/**
	 * @param string $path
	 * @param int $expectResponse
	 * @return array
	 *
	 */
	public function testPath($path, $expectResponse = 0): array {
		$tests = [];
		$testResults = [];
		$results = [];
		$optionSets = ['defaults' => $this->pathFinder()->getDefaultOptions(), 'noPagePaths' => ['usePagePaths' => false], 'noGlobalUnique' => ['useGlobalUnique' => false], 'noHistory' => ['useHistory' => false], 'excludeRoot' => ['useExcludeRoot' => true]];
		foreach($optionSets as $name => $options) {
			$options['test'] = true;
			$result = $this->pathFinder()->get($path, $options);
			$test = $result['test'];
			$results[$name] = $result;
			$tests[$name] = $test;
		}
		$defaultTest = $tests['defaults'];
		foreach(array_keys($optionSets) as $name) {
			$test = $tests[$name];
			$result = $results[$name];
			if($expectResponse && $result['response'] != $expectResponse) {
				$status = "FAIL ($result[response] != $expectResponse)";
			} else {
				$status = ($test === $defaultTest ? 'OK' : 'FAIL');
			}
			$testResults[] = ['name' => $name, 'status' => $status, 'test' => $test];
		}

		return $testResults;
	}

	/**
	 * @param Page $item
	 * @return array
	 *
	 */
	public function testPage(Page $item): array {
		$languages = $this->languages();
		$testResults = [];
		$defaultPath = $item->path();
		if($languages) {
			foreach($languages as $language) {
				/** @var Language $language */
				$path = $item->localPath($language);
				if($language->isDefault() || $path === $defaultPath) {
					$expect = 200;
				} else {
					$expect = $item->get("status$language") > 0 ? 200 : 300;
				}
				$testResults["$language->name:$path"] = $this->testPath($path, $expect);
			}
		} else {
			$path = $item->path();
			$testResults[$path] = $this->testPath($path, 200);
		}
		return $testResults;
	}

	/**
	 * @param string|PageArray $selector
	 * @return array
	 *
	 */
	public function testPages($selector): array {
		if($selector instanceof PageArray) {
			$items = $selector;
		} else {
			$items = $this->pages->find($selector);
		}
		$testResults = [];
		foreach($items as $item) {
			$testResults = array_merge($testResults, $this->testPage($item));
		}
		return $testResults;
	}
}
