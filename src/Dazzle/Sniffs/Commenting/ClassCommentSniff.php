<?php
/**
 * Parses and verifies the doc comments for classes.
 *
 * PHP version 7
 *
 * @category   PHP
 * @package    Dazzle.Framework
 * @subpackage Application
 * @author     Dazzle Software <support@dazzlesoftware.org>
 * @copyright  Copyright (C) 2018 - 2020 Dazzle Software, LLC. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE.md
 * @link       https://github.com/dazzlesoftware/dazzle-coding-standard
 */

namespace Dazzle\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use Dazzle\Sniffs\Commenting\FileCommentSniff as DazzleFileCommentSniff;

class ClassCommentSniff extends DazzleFileCommentSniff
{
    /**
     * Tags in correct order and related info.
     *
     * @var array
     */
    protected $tags = [
        '@category'   => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@package'    => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@subpackage' => [
            'required'       => false,
            'allow_multiple' => false,
        ],
        '@author'     => [
            'required'       => false,
            'allow_multiple' => true,
        ],
        '@copyright'  => [
            'required'       => false,
            'allow_multiple' => true,
        ],
        '@license'    => [
            'required'       => false,
            'allow_multiple' => false,
        ],
		
/*         
        '@version'    => [
            'required'       => false,
            'allow_multiple' => false,
        ],
*/
        '@link'       => [
            'required'       => false,
            'allow_multiple' => true,
        ],
        '@see'        => [
            'required'       => false,
            'allow_multiple' => true,
        ],
        '@since'      => [
            'required'       => true,
            'allow_multiple' => false,
        ],
        '@deprecated' => [
            'required'       => false,
            'allow_multiple' => false,
        ],
    ];

    /**
     * Blacklisted tags
     *
     * @var array<string>
     */
    protected $blacklist = array(
        '@version',
    );

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT,
        ];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens    = $phpcsFile->getTokens();
        $type      = strtolower($tokens[$stackPtr]['content']);
        $errorData = [$type];

        $find   = Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;

        $commentEnd = $phpcsFile->findPrevious($find, ($stackPtr - 1), null, true);
        if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['code'] !== T_COMMENT
        ) {
            $errorData[] = $phpcsFile->getDeclarationName($stackPtr);
            $phpcsFile->addError('Missing doc comment for %s %s', $stackPtr, 'Missing', $errorData);
            $phpcsFile->recordMetric($stackPtr, 'Class has doc comment', 'no');
            return;
        }

        $phpcsFile->recordMetric($stackPtr, 'Class has doc comment', 'yes');

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            $phpcsFile->addError('You must use "/**" style comments for a %s comment', $stackPtr, 'WrongStyle', $errorData);
            return;
        }

        // Check each tag.
        $this->processTags($phpcsFile, $stackPtr, $tokens[$commentEnd]['comment_opener']);

    }//end process()


    /**
     * Process the version tag.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param array                       $tags      The tokens for these tags.
     *
     * @return void
     */
    protected function processVersion($phpcsFile, array $tags)
    {
        $tokens = $phpcsFile->getTokens();
        foreach ($tags as $tag) {
            if ($tokens[($tag + 2)]['code'] !== T_DOC_COMMENT_STRING) {
                // No content.
                continue;
            }

            $content = $tokens[($tag + 2)]['content'];
            if ((strstr($content, 'Release:') === false)) {
                $error = 'Invalid version "%s" in doc comment; consider "Release: <package_version>" instead';
                $data  = [$content];
                $phpcsFile->addWarning($error, $tag, 'InvalidVersion', $data);
            }
        }

    }//end processVersion()


}//end class
