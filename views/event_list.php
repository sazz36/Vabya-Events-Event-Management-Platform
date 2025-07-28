<?php
session_start();
$pageTitle = "Events";

// Simulated sample data (you should fetch this from a database in a real app)
$events = $events ?? []; // Prevent warning if $events is not set

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Upcoming Events</h1>
    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="/admin/create_event.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Event
        </a>
    <?php endif; ?>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="input-group">
            <input type="text" id="searchEvents" class="form-control" placeholder="Search events...">
            <button class="btn btn-outline-secondary" type="button">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </div>
    <div class="col-md-6">
        <select id="filterCategory" class="form-select">
            <option value="">All Categories</option>
            <option value="concert">Concerts</option>
            <option value="conference">Conferences</option>
            <option value="workshop">Workshops</option>
            <option value="sports">Sports</option>
        </select>
    </div>
</div>

<div class="row" id="eventList">
    <?php if (!empty($events)): ?>
        <?php foreach ($events as $event): ?>
            <div class="col-md-6 col-lg-4 mb-4 event-card" 
                 data-title="<?php echo htmlspecialchars(strtolower($event['title'])); ?>"
                 data-category="<?php echo htmlspecialchars(strtolower($event['category'] ?? 'general')); ?>">
                <div class="card h-100">
                    <div class="card-img-top event-card-image" 
                         style="background-image: url('https://source.unsplash.com/random/600x400/?event,<?php echo urlencode($event['title']); ?>'); height: 200px; background-size: cover; background-position: center;">
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                        <p class="card-text text-muted">
                            <i class="bi bi-calendar-event"></i> 
                            <?php echo date('M j, Y', strtotime($event['date'])); ?> 
                            at <?php echo date('g:i A', strtotime($event['time'])); ?>
                        </p>
                        <p class="card-text text-muted">
                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['venue']); ?>
                        </p>
                        <p class="card-text"><?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary rounded-pill">Rs <?php echo number_format($event['price'], 2); ?></span>
                            <a href="/event_detail.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                            <button class="btn btn-sm btn-success ms-2">Book</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">No events available at the moment.</p>
    <?php endif; ?>
</div>

<div class="text-center mt-3">
    <button id="loadMore" class="btn btn-outline-primary">Load More Events</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchEvents');
    const categoryFilter = document.getElementById('filterCategory');
    const eventCards = document.querySelectorAll('.event-card');

    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();

        eventCards.forEach(card => {
            const title = card.getAttribute('data-title');
            card.style.display = title.includes(searchTerm) ? 'block' : 'none';
        });
    });

    // Category filter
    categoryFilter.addEventListener('change', function() {
        const selected = this.value;

        eventCards.forEach(card => {
            const category = card.getAttribute('data-category');
            card.style.display = !selected || category === selected ? 'block' : 'none';
        });
    });

    // Load more functionality (simulated)
    document.getElementById('loadMore').addEventListener('click', function() {
        alert('In a complete implementation, this would load more events from the server.');
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
