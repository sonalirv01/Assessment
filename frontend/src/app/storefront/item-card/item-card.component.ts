import { Component, HostListener, Input, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { JewelleryItem } from '../../core/models/item.model';

@Component({
  selector: 'app-item-card',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './item-card.component.html',
})
export class ItemCardComponent {
  @Input({ required: true }) item!: JewelleryItem;

  isBreakdownExpanded = signal(false);
  activeImageIndex = signal(0);
  isZoomOpen = signal(false);

  toggleBreakdown(): void {
    this.isBreakdownExpanded.set(!this.isBreakdownExpanded());
  }

  selectImage(index: number): void {
    this.activeImageIndex.set(index);
  }

  showPrevImage(event: Event): void {
    event.stopPropagation();
    const count = this.item.images.length;
    this.activeImageIndex.set((this.activeImageIndex() - 1 + count) % count);
  }

  showNextImage(event: Event): void {
    event.stopPropagation();
    const count = this.item.images.length;
    this.activeImageIndex.set((this.activeImageIndex() + 1) % count);
  }

  openZoom(): void {
    if (this.item.images.length > 0) {
      this.isZoomOpen.set(true);
    }
  }

  closeZoom(): void {
    this.isZoomOpen.set(false);
  }

  @HostListener('document:keydown.escape')
  onEscapeKey(): void {
    this.closeZoom();
  }
}
