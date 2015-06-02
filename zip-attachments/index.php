<?php

/* -AFTERLOGIC LICENSE HEADER- */

class_exists('CApi') or die();

CApi::Inc('common.plugins.expand-attachment');

class CZipAttachmentsPlugin extends AApiExpandAttachmentPlugin
{
	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct(CApiPluginManager $oPluginManager)
	{
		parent::__construct('1.0', $oPluginManager);
	}

	public function IsMimeTypeSupported($sMimeType, $sFileName = '')
	{
		return in_array($sMimeType, array('application/zip', 'application/x-zip')) && class_exists('ZipArchive');
	}

	public function ExpandAttachment($oAccount, $sMimeType, $sFullFilePath, $oApiFileCache)
	{
		$mResult = array();

		$oZip = new ZipArchive();
		if ($oZip->open($sFullFilePath))
		{
			for ($iIndex = 0; $iIndex < $oZip->numFiles; $iIndex++)
			{
				$aStat = $oZip->statIndex($iIndex);
				$sFile = $oZip->getFromIndex($iIndex);
				$iFileSize = $sFile ? strlen($sFile) : 0;

				if ($aStat && $sFile && 0 < $iFileSize && !empty($aStat['name']))
				{
					$sFileName = \MailSo\Base\Utils::Utf8Clear(basename($aStat['name']));
					$sTempName = md5(microtime(true).rand(1000, 9999));

					if ($oApiFileCache->Put($oAccount, $sTempName, $sFile))
					{
						unset($sFile);

						$sMimeType = \MailSo\Base\Utils::MimeContentType($sFileName);

						$mResult[] = array(
							'FileName' => $sFileName,
							'MimeType' => $sMimeType,
							'Size' => $iFileSize,
							'TempName' => $sTempName
						);
					}
					else
					{
						unset($sFile);
					}
				}
			}

			$oZip->close();
		}

		return $mResult;
	}
}

return new CZipAttachmentsPlugin($this);
