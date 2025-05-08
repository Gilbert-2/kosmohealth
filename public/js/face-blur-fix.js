// Fix for face blur
(function() {
  // Override the applyBlurToFaceBox method
  if (window.FaceProcessingService) {
    const originalMethod = window.FaceProcessingService.prototype.applyBlurToFaceBox;
    
    window.FaceProcessingService.prototype.applyBlurToFaceBox = function(ctx, box, isPredicted) {
      // Call original method first
      originalMethod.call(this, ctx, box, isPredicted);
      
      // Additional fixes for blur
      if (this.glassmorphismEnabled) {
        // Make sure the blur is applied correctly
        const expandFactor = isPredicted ? 0.25 : 0.15;
        
        // Calculate expanded box
        const expandedBox = {
          x: Math.max(0, box.x - box.width * expandFactor),
          y: Math.max(0, box.y - box.height * expandFactor),
          width: box.width * (1 + expandFactor * 2),
          height: box.height * (1 + expandFactor * 2)
        };
        
        // Apply stronger blur
        ctx.filter = `blur(${this.blurIntensity * 1.5}px)`;
        
        // Redraw with blur
        ctx.drawImage(
          ctx.canvas,
          expandedBox.x, expandedBox.y, expandedBox.width, expandedBox.height,
          expandedBox.x, expandedBox.y, expandedBox.width, expandedBox.height
        );
      }
    };
  }
})();
