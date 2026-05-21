<?php 
require_once 'includes/db_connect.php';
include 'includes/header.php'; 

$today = date('Y-m-d');

// Fetch for Calendar
$calendar_events = [];
$res = $conn->query("SELECT id, title, event_date FROM events WHERE is_deleted = 0");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $calendar_events[$row['event_date']][] = $row;
    }
}

// Full Details if ID is set
$single_event = null;
if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ? AND is_deleted = 0");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            $single_event = $res->fetch_assoc();
        }
    }
}
?>

<!-- Page Header -->
<style>
    @keyframes ken-burns-zoom {
        0% { transform: scale(1); opacity: 0; }
        5% { opacity: 1; }
        20% { transform: scale(1.1); opacity: 1; }
        25% { transform: scale(1.15); opacity: 0; }
        100% { opacity: 0; }
    }
    .header-slider-container {
        position: absolute;
        inset: 0;
        z-index: 0;
        overflow: hidden;
        background: #0f172a;
    }
    .header-slide {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        opacity: 0;
        animation: ken-burns-zoom 25s linear infinite;
    }
    .slide-1 { background-image: url('https://images.unsplash.com/photo-1559839734-2b71f1536783?q=80&w=2070&auto=format&fit=crop'); animation-delay: 0s; }
    .slide-2 { background-image: url('https://images.unsplash.com/photo-1579684385127-1ef15d508118?q=80&w=2080&auto=format&fit=crop'); animation-delay: 5s; }
    .slide-3 { background-image: url('https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?q=80&w=2070&auto=format&fit=crop'); animation-delay: 10s; }
    .slide-4 { background-image: url('https://images.unsplash.com/photo-1551076805-e1869033e561?q=80&w=2070&auto=format&fit=crop'); animation-delay: 15s; }
    .slide-5 { background-image: url('https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?q=80&w=1932&auto=format&fit=crop'); animation-delay: 20s; }
</style>

<!-- Hero Section -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<section class="relative py-32 lg:py-48 bg-slate-900 overflow-hidden">
    <!-- Background Slider -->
    <div class="header-slider-container">
        <div class="header-slide slide-1"></div>
        <div class="header-slide slide-2"></div>
        <div class="header-slide slide-3"></div>
        <div class="header-slide slide-4"></div>
        <div class="header-slide slide-5"></div>
        <div class="absolute inset-0 bg-slate-900/50"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <div class="inline-flex items-center px-4 py-2 bg-blue-500/20 text-blue-300 rounded-full mb-8 border border-blue-500/30" data-aos="fade-up">
            <span class="text-xs font-bold uppercase tracking-[0.2em]">Hospital Calendar</span>
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-white leading-tight mb-8" data-aos="fade-up" data-aos-delay="100">
            Events & <span class="text-blue-400">Programs</span>
        </h1>
        <p class="text-xl text-blue-100 font-medium max-w-2xl mx-auto opacity-90" data-aos="fade-up" data-aos-delay="200">
            Stay connected with our community through health workshops, outreach programs, and medical seminars.
        </p>
    </div>
</section>

<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if($single_event): ?>
            <!-- Single Event Detailed View -->
            <div class="mb-24 animate-in fade-in slide-in-from-bottom-10 duration-700">
                <a href="events.php" class="inline-flex items-center text-slate-500 font-bold mb-8 hover:text-blue-600 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to all events
                </a>
                <div class="grid lg:grid-cols-2 gap-16 items-start">
                    <div class="rounded-[40px] overflow-hidden shadow-2xl">
                        <?php if($single_event['image']): ?>
                            <img src="assets/images/events/<?php echo $single_event['image']; ?>" class="w-full aspect-[4/3] object-cover" alt="<?php echo htmlspecialchars($single_event['title']); ?>">
                        <?php else: ?>
                            <div class="w-full aspect-[4/3] bg-blue-600 flex items-center justify-center text-white">
                                <svg class="w-32 h-32 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="inline-flex items-center px-4 py-2 bg-blue-50 text-blue-600 rounded-2xl mb-6">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-sm font-black uppercase tracking-widest"><?php echo date('M d, Y', strtotime($single_event['event_date'])); ?></span>
                        </div>
                        <h2 class="text-4xl lg:text-5xl font-black text-slate-900 mb-8"><?php echo htmlspecialchars($single_event['title']); ?></h2>
                        <div class="prose prose-lg text-slate-500 font-medium leading-relaxed max-w-none">
                            <?php echo nl2br(htmlspecialchars($single_event['description'])); ?>
                        </div>
                        <div class="mt-12 p-8 bg-slate-50 rounded-[32px] border border-slate-100">
                            <h4 class="font-black text-slate-900 mb-2">Interested in this event?</h4>
                            <p class="text-sm text-slate-500 mb-6">Contact our help desk for registration or more information.</p>
                            <a href="contact.php" class="inline-flex items-center px-8 py-4 bg-blue-600 text-white rounded-2xl font-black text-sm shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all">
                                Inquiry Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="border-slate-100 my-24">
        <?php endif; ?>

        <div class="grid lg:grid-cols-3 gap-16">
            <!-- Events List -->
            <div class="lg:col-span-2">
                <h3 class="text-3xl font-black text-slate-900 mb-12">Upcoming Programs</h3>
                <div class="space-y-8">
                    <?php 
                    $list_res = $conn->query("SELECT * FROM events WHERE is_deleted = 0 AND event_date >= '$today' ORDER BY event_date ASC");
                    if($list_res && $list_res->num_rows > 0):
                        while($item = $list_res->fetch_assoc()):
                    ?>
                        <div class="group flex flex-col md:flex-row gap-8 p-6 bg-white rounded-[32px] border border-slate-100 hover:shadow-2xl hover:shadow-blue-100 transition-all duration-500">
                            <div class="md:w-48 h-48 rounded-2xl overflow-hidden shrink-0">
                                <?php if($item['image']): ?>
                                    <img src="assets/images/events/<?php echo $item['image']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                <?php else: ?>
                                    <div class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col justify-center">
                                <span class="text-xs font-black text-blue-600 uppercase tracking-widest mb-2"><?php echo date('M d, Y', strtotime($item['event_date'])); ?></span>
                                <h4 class="text-2xl font-black text-slate-900 mb-4 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p class="text-slate-500 text-sm font-medium mb-6 line-clamp-2"><?php echo htmlspecialchars($item['description']); ?></p>
                                <a href="?id=<?php echo $item['id']; ?>" class="inline-flex items-center text-slate-900 font-black text-xs uppercase tracking-widest group-hover:text-blue-600 transition-colors">
                                    View details <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <p class="text-slate-400 font-bold italic">No upcoming events found.</p>
                    <?php endif; ?>
                </div>

                <h3 class="text-3xl font-black text-slate-900 mt-24 mb-12 opacity-50">Past Activities</h3>
                <div class="space-y-6 opacity-60">
                    <?php 
                    $past_res = $conn->query("SELECT * FROM events WHERE event_date < '$today' ORDER BY event_date DESC LIMIT 5");
                    if ($past_res):
                        while($past = $past_res->fetch_assoc()):
                    ?>
                        <div class="flex items-center justify-between p-6 bg-slate-50 rounded-2xl">
                            <div>
                                <h5 class="font-bold text-slate-700"><?php echo htmlspecialchars($past['title']); ?></h5>
                                <p class="text-xs text-slate-400 font-bold mt-1"><?php echo date('M d, Y', strtotime($past['event_date'])); ?></p>
                            </div>
                            <a href="?id=<?php echo $past['id']; ?>" class="text-slate-400 hover:text-blue-600 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    <?php 
                        endwhile;
                    endif; 
                    ?>
                </div>
            </div>

            <!-- Calendar Sidebar -->
            <div class="lg:col-span-1">
                <div class="sticky top-32">
                    <div class="bg-white rounded-[40px] shadow-2xl border border-slate-100 overflow-hidden">
                        <?php 
                        $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
                        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
                        
                        $prev_month = $month - 1;
                        $prev_year = $year;
                        if($prev_month < 1) { $prev_month = 12; $prev_year--; }
                        
                        $next_month = $month + 1;
                        $next_year = $year;
                        if($next_month > 12) { $next_month = 1; $next_year++; }
                        
                        $date_obj = DateTime::createFromFormat('!m', $month);
                        $month_name = $date_obj->format('F');
                        ?>
                        <div class="p-8 bg-blue-600 text-white text-center relative">
                            <div class="flex justify-between items-center mb-2">
                                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                </a>
                                <h4 class="text-xl font-black"><?php echo $month_name . ' ' . $year; ?></h4>
                                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </div>
                            <p class="text-blue-200 text-xs font-bold uppercase tracking-widest">Interactive Calendar</p>
                        </div>
                        <div class="p-6">
                            <?php 
                            $first_day = mktime(0, 0, 0, $month, 1, $year);
                            $days_in_month = date('t', $first_day);
                            $day_of_week = date('w', $first_day);
                            
                            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            ?>
                            <div class="grid grid-cols-7 gap-2 mb-4">
                                <?php foreach($days as $day): ?>
                                    <div class="text-[10px] font-black text-slate-400 text-center uppercase"><?php echo $day; ?></div>
                                <?php endforeach; ?>
                            </div>
                            <div class="grid grid-cols-7 gap-2">
                                <?php 
                                for($i = 0; $i < $day_of_week; $i++) echo '<div></div>';
                                
                                for($day = 1; $day <= $days_in_month; $day++):
                                    $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                    $has_event = isset($calendar_events[$current_date]);
                                    $is_today = ($current_date == date('Y-m-d'));
                                ?>
                                    <div class="relative aspect-square flex items-center justify-center text-sm font-bold rounded-xl transition-all group
                                        <?php echo $is_today ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : ($has_event ? 'bg-blue-50 text-blue-600 cursor-pointer hover:bg-blue-100' : 'text-slate-400'); ?>">
                                        <?php echo $day; ?>
                                        <?php if($has_event): ?>
                                            <div class="absolute bottom-1 w-1 h-1 bg-blue-400 rounded-full"></div>
                                            <!-- Tooltip -->
                                            <div class="event-popover absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-slate-900 text-white p-3 rounded-xl text-xs z-20 hidden group-hover:block pointer-events-none shadow-2xl">
                                                <?php foreach($calendar_events[$current_date] as $ev): ?>
                                                    <div class="mb-1 last:mb-0">• <?php echo htmlspecialchars($ev['title']); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="p-8 bg-slate-50 border-t border-slate-100 text-center">
                            <p class="text-xs text-slate-500 font-medium">Highlighted dates indicate scheduled hospital activities.</p>
                        </div>
                    </div>

                    <!-- Additional Sidebar CTA -->
                    <div class="mt-8 rounded-[40px] bg-gradient-to-br from-indigo-600 to-blue-700 p-10 text-white relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
                        <h4 class="text-2xl font-black mb-4 relative z-10">Don't Miss Out!</h4>
                        <p class="text-indigo-100 text-sm mb-8 relative z-10 leading-relaxed font-medium">Get notifications about new events directly in your inbox.</p>
                        <form id="newsletterForm" class="relative z-10">
                            <input type="email" name="email" placeholder="Your Email" required class="w-full bg-white/10 border-white/20 rounded-2xl px-6 py-4 text-sm text-white placeholder:text-indigo-200 focus:ring-2 focus:ring-white/50 outline-none mb-4">
                            <button type="submit" class="w-full bg-white text-indigo-600 py-4 rounded-2xl font-black text-sm hover:bg-indigo-50 transition-all flex items-center justify-center gap-2 group">
                                <span>Subscribe</span>
                                <i data-lucide="send" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </form>

                        <script>
                            document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                                e.preventDefault();
                                const btn = this.querySelector('button');
                                const originalText = btn.innerHTML;
                                
                                btn.disabled = true;
                                btn.innerHTML = 'Subscribing...';

                                const formData = new FormData(this);
                                fetch('<?php echo BASE_URL; ?>/api/subscribe.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if(data.success) {
                                        Swal.fire({
                                            title: 'Subscribed!',
                                            text: data.message,
                                            icon: 'success',
                                            confirmButtonColor: '#4f46e5',
                                            customClass: { popup: 'rounded-[32px]' }
                                        });
                                        this.reset();
                                    } else {
                                        Swal.fire({
                                            title: 'Wait!',
                                            text: data.message,
                                            icon: 'info',
                                            confirmButtonColor: '#4f46e5',
                                            customClass: { popup: 'rounded-[32px]' }
                                        });
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    Swal.fire('Error', 'Could not connect to the server.', 'error');
                                })
                                .finally(() => {
                                    btn.disabled = false;
                                    btn.innerHTML = originalText;
                                    lucide.createIcons();
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .event-popover::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #0f172a transparent transparent transparent;
    }
    .relative:hover .event-popover {
        display: block;
    }
</style>

<?php include 'includes/footer.php'; ?>
