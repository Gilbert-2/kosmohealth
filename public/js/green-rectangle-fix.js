// Fix for green rectangle tracking
(function() {
  // Override the drawFaceBox method
  if (window.FaceProcessingService) {
    const originalMethod = window.FaceProcessingService.prototype.drawFaceBox;
    
    window.FaceProcessingService.prototype.drawFaceBox = function(ctx, box, isPredicted) {
      // Make sure the rectangle is visible
      ctx.save();
      
      // Use different styles for detected vs predicted faces
      if (isPredicted) {
        // Dashed line for predicted faces
        ctx.strokeStyle = '#00FF00';
        ctx.lineWidth = 2;
        ctx.setLineDash([5, 3]);
        ctx.strokeRect(box.x, box.y, box.width, box.height);
        
        // Add 'Tracking' text
        ctx.font = 'bold 12px Arial';
        ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
        ctx.fillText('Tracking', box.x + 1, box.y - 6);
        ctx.fillStyle = '#00FF00';
        ctx.fillText('Tracking', box.x, box.y - 7);
      } else {
        // Solid line for detected faces
        ctx.strokeStyle = '#00FF00';
        ctx.lineWidth = 3;
        ctx.setLineDash([]);
        ctx.strokeRect(box.x, box.y, box.width, box.height);
      }
      
      ctx.restore();
      
      // Call original method
      if (originalMethod) {
        originalMethod.call(this, ctx, box, isPredicted);
      }
    };
  }
})();
