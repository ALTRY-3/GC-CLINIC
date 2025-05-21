import cv2
import numpy as np
import sys
import os

def preprocess_image(image_path):
    # Read the image
    img = cv2.imread(image_path)
    
    # Convert to grayscale
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    
    # Apply adaptive thresholding
    thresh = cv2.adaptiveThreshold(gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C, 
                                 cv2.THRESH_BINARY, 11, 2)
    
    # Apply dilation to connect text components
    kernel = np.ones((1, 1), np.uint8)
    dilation = cv2.dilate(thresh, kernel, iterations=1)
    
    # Apply erosion to remove noise
    erosion = cv2.erode(dilation, kernel, iterations=1)
    
    # Apply Gaussian blur
    blur = cv2.GaussianBlur(erosion, (3, 3), 0)
    
    # Apply sharpening
    kernel = np.array([[-1,-1,-1], [-1,9,-1], [-1,-1,-1]])
    sharpened = cv2.filter2D(blur, -1, kernel)
    
    # Save the preprocessed image
    output_path = os.path.splitext(image_path)[0] + '_preprocessed.jpg'
    cv2.imwrite(output_path, sharpened)
    
    return output_path

if __name__ == "__main__":
    if len(sys.argv) > 1:
        image_path = sys.argv[1]
        preprocessed_path = preprocess_image(image_path)
        print(preprocessed_path)
    else:
        print("Error: No image path provided") 