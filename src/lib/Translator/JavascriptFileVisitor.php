<?php

namespace EzSystems\EzPlatformAdminUi\Translator;

use JMS\TranslationBundle\Model\FileSource;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Model\MessageCatalogue;
use JMS\TranslationBundle\Translation\Extractor\FileVisitorInterface;
use Peast\Peast;
use Peast\Syntax\Exception;
use SplFileInfo;

class JavascriptFileVisitor implements FileVisitorInterface
{
    // Example match: /** @Desc("Default text") */
    const COMMENT_DESC_PATTERN = '/\/\*\*?\s*@Desc\(["\'](.*)["\']\)\s*\*\//';

    /**
     * {@inheritdoc}
     *
     * It will extract translations with domains and default translation (if exists) from
     * tokenized JS input represented by: Translator.get('DOMAIN:key' COMMENT_DESC_PATTERN, ...).
     * Check COMMENT_DESC_PATTERN constant description for details.
     */
    public function visitFile(SplFileInfo $file, MessageCatalogue $catalogue)
    {
        if ('.js' !== substr($file, -3)) {
            return;
        }

        try {
            $tokens = Peast::latest(file_get_contents($file), [
                'comments' => true,
                'jsx' => true,
                'sourceType' => Peast::SOURCE_TYPE_MODULE
            ])->tokenize();
        }
        catch (Exception $exception) {
            return;
        }

        $length = count($tokens);

        for ($i = 0; $i < $length; $i++) {
            if (
                isset($tokens[$i + 0]) && ($tokens[$i + 0]->getType() === 'Identifier' && $tokens[$i + 0]->getValue() === 'Translator') &&
                isset($tokens[$i + 1]) && ($tokens[$i + 1]->getType() === 'Punctuator' && $tokens[$i + 1]->getValue() === '.') &&
                isset($tokens[$i + 2]) && ($tokens[$i + 2]->getType() === 'Identifier' && $tokens[$i + 2]->getValue() === 'get') &&
                isset($tokens[$i + 3]) && ($tokens[$i + 3]->getType() === 'Punctuator' && $tokens[$i + 3]->getValue() === '(') &&
                isset($tokens[$i + 4]) && ($tokens[$i + 4]->getType() === 'String')
            ) {
                $string = trim($tokens[$i + 4]->getValue(), " \t\n\r\0\x0B'\"");
                $parts = array_reverse(explode(':', $string, 2));

                $message = new Message($parts[0], $domain = $parts[1] ?? 'messages');
                $message->addSource(new FileSource((string) $file));

                if (isset($tokens[$i + 5]) && $tokens[$i + 5]->getType() === 'Comment') {
                    preg_match(self::COMMENT_DESC_PATTERN, $tokens[$i + 5]->getValue(), $matches);

                    if (!empty($matches)) {
                        $message->setDesc($matches[1]);
                    }
                }

                $catalogue->add($message);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitPhpFile(SplFileInfo $file, MessageCatalogue $catalogue, array $ast)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function visitTwigFile(SplFileInfo $file, MessageCatalogue $catalogue, \Twig_Node $ast)
    {
    }
}