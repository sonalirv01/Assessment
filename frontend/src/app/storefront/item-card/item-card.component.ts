import { Component, Input, signal } from '@angular/core';
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

  toggleBreakdown(): void {
    this.isBreakdownExpanded.set(!this.isBreakdownExpanded());
  }

  selectImage(index: number): void {
    this.activeImageIndex.set(index);
  }
}
