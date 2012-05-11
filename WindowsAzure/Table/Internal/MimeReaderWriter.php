<?php

/**
 * LICENSE: Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * PHP version 5
 *
 * @category  Microsoft
 * @package   WindowsAzure\Table\Internal
 * @author    Abdelrahman Elogeel <Abdelrahman.Elogeel@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      http://pear.php.net/package/azure-sdk-for-php
 */
 
namespace WindowsAzure\Table\Internal;
require_once 'PEAR.php';
require_once 'Mail/mimePart.php';
require_once 'Mail/mimeDecode.php';
use WindowsAzure\Common\Internal\Resources;

/**
 * Reads and writes MIME for batch API.
 *
 * @category  Microsoft
 * @package   WindowsAzure\Table\Internal
 * @author    Abdelrahman Elogeel <Abdelrahman.Elogeel@microsoft.com>
 * @copyright 2012 Microsoft Corporation
 * @license   http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/azure-sdk-for-php
 */
class MimeReaderWriter implements IMimeReaderWriter
{
    /**
     * Given array of MIME parts in raw string, this function converts them into MIME
     * representation. 
     * 
     * @param array $bodyPartContents The MIME body parts.
     * 
     * @return array Returns array with two elements 'headers' and 'body' which
     * represents the MIME message.
     */
    public function encodeMimeMultipart($bodyPartContents)
    {
        $count         = count($bodyPartContents);
        $mimeType      = Resources::MULTIPART_MIXED_TYPE;
        $batchGuid     = strtolower(trim(com_create_guid(), '{}'));
        $batchId       = sprintf('batch_%s', $batchGuid);
        $contentType1  = array('content_type' => "$mimeType");
        $changeSetGuid = strtolower(trim(com_create_guid(), '{}'));
        $changeSetId   = sprintf('changeset_%s', $changeSetGuid);
        $contentType2  = array('content_type' => "$mimeType; boundary=$changeSetId");
        $options       = array(
            'encoding'     => 'binary',
            'content_type' => Resources::HTTP_TYPE
        );

        // Create changeset MIME part
        $changeSet = new \Mail_mimePart();
        
        for ($i = 0; $i < $count; $i++) {
            $changeSet->addSubpart($bodyPartContents[$i], $options);
        }
        
        // Encode the changeset MIME part
        $changeSetEncoded = $changeSet->encode($changeSetId);
        
        // Create the batch MIME part
        $batch = new \Mail_mimePart(Resources::EMPTY_STRING, $contentType1);
        
        // Add changeset encoded to batch MIME part
        $batch->addSubpart($changeSetEncoded['body'], $contentType2);
        
        // Encode batch MIME part
        $batchEncoded = $batch->encode($batchId);
        
        return $batchEncoded;
    }
    
    /**
     * Parses given mime HTTP response body into array. Each array element 
     * represents a change set result.
     * 
     * @param string $mimeBody The raw MIME body result.
     * 
     * @return array
     */
    public function decodeMimeMultipart($mimeBody)
    {
        $params['include_bodies'] = true;
        $params['input']          = $mimeBody;
        $structure                = \Mail_mimeDecode::decode($params);
        $parts                    = $structure->parts;
        $bodies                   = array();
        
        foreach ($parts as $part) {
            $bodies[] = $part->body;
        }
        
        return $bodies;
    }
}

?>
