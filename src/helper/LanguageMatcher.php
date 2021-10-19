<?php
namespace XAF\helper;

class LanguageMatcher
{
	/**
	 * @param array $preferredLanguageTags
	 * @param array $availableLanguageTags
	 * @return string|null
	 */
	static public function findBestAvailableLanguage( array $preferredLanguageTags, array $availableLanguageTags )
	{
		foreach( $preferredLanguageTags as $preferredTag )
		{
			$bestMatchSoFar = null;
			$bestMatchScoreSoFar = 0;
			foreach( $availableLanguageTags as $availableTag )
			{
				$matchScore = self::computeMatchScore($preferredTag, $availableTag);
				if( $matchScore > $bestMatchScoreSoFar )
				{
					$bestMatchSoFar = $availableTag;
					$bestMatchScoreSoFar = $matchScore;
				}
			}
			if( $bestMatchScoreSoFar > 0 )
			{
				return $bestMatchSoFar;
			}
		}

		return isset($availableLanguageTags[0]) ? $availableLanguageTags[0] : null;
	}

	/**
	 * Compute the closeness of two language tags
	 *
	 * Each matching part from the beginning counts 10. Each non-matching part in either code lowers the score by 1.
	 *
	 * "en-nz" vs. "en-nz": 20
	 * "en" vs. "en": 10
	 * "en" vs. "en-nz": 10 - 1 = 9
	 * "en-gb" vs. "en-nz": 10 - 1 - 1 = 8
	 *
	 * @param string $tag1
	 * @param string $tag2
	 * @return int Score based on number of matching parts and closeness of the part count
	 */
	static protected function computeMatchScore( $tag1, $tag2 )
	{
		$parts1 = LanguageTagHelper::split($tag1);
		$parts1Count = \sizeof($parts1);

		$parts2 = LanguageTagHelper::split($tag2);
		$parts2Count = \sizeof($parts2);

		$commonPartCount = \min($parts1Count, $parts2Count);

		$i = 0;
		while( $i < $commonPartCount && $parts1[$i] == $parts2[$i] )
		{
			$i++;
		}
		if( $i < 1 )
		{
			return 0;
		}

		$nonMatchingPartCount = ($parts1Count - $i) + ($parts2Count - $i);
		return $i * 10 - $nonMatchingPartCount;
	}
}
