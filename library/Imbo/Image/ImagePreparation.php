<?php
/**
 * Imbo
 *
 * Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * * The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package Image
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */

namespace Imbo\Image;

use Imbo\Http\Request\RequestInterface,
    Imbo\Exception\ImageException,
    Imbo\Image\Image;

/**
 * Image preparation
 *
 * @package Image
 * @author Christer Edvartsen <cogo@starzinger.net>
 * @copyright Copyright (c) 2011-2012, Christer Edvartsen <cogo@starzinger.net>
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/christeredvartsen/imbo
 */
class ImagePreparation implements ImagePreparationInterface {
    /**
     * @see Imbo\Image\ImagePreparationInterface::prepareImage()
     */
    public function prepareImage(RequestInterface $request, ImageInterface $image) {
        // Fetch image data from input
        $imageBlob = $request->getRawData();

        if (empty($imageBlob)) {
            throw new ImageException('No image attached', 400);
        }

        // Calculate hash
        $actualHash = md5($imageBlob);

        // Get image identifier from request
        $imageIdentifier = $request->getImageIdentifier();

        if ($actualHash !== $imageIdentifier) {
            throw new ImageException('Hash mismatch', 400);
        }

        // Use the file info extension to fetch the mime type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($imageBlob);

        if (!Image::supportedMimeType($mime)) {
            throw new ImageException('Unsupported image type: ' . $mime, 415);
        }

        $extension = Image::getFileExtension($mime);

        // Store file to disk and use getimagesize() to fetch width/height
        $tmpFile = tempnam(sys_get_temp_dir(), 'Imbo_uploaded_image');
        file_put_contents($tmpFile, $imageBlob);
        $size = getimagesize($tmpFile);
        unlink($tmpFile);

        // Store relevant information in the image instance
        $image->setMimeType($mime)
              ->setExtension($extension)
              ->setBlob($imageBlob)
              ->setWidth($size[0])
              ->setHeight($size[1]);

        return $this;
    }
}
