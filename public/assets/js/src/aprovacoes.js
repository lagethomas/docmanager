/**
 * ==========================================================================
 * DOCMANAGER PRO - MÓDULO DE APROVAÇÕES DE DOCUMENTOS
 * ==========================================================================
 */
'use strict';

var Approvals = (function () {
  var ALL_DOCS = [];
  var STATS = {};
  var CURRENT_STATUS = 'pendente';
  var VIEW_MODE = localStorage.getItem('approvals_view') || 'card';
  var SEARCH_TIMER = null;
  var CURRENT_PREVIEW_DOC = null;
  var CURRENT_REJECT_ID = null;
  var CURRENT_PAGE = 1;
  var PER_PAGE = 10;

  var ICONS_MAP = {
    folder: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
    doc: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
    auth: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
    compute: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
    network: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
    devops: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    db: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
    tool: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    idea: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><line x1="9" y1="18" x2="15" y2="18"/><line x1="10" y1="22" x2="14" y2="22"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5.76.76 1.23 1.52 1.41 2.5z"/></svg>',
    goal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    rocket: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4.5c1.47-1.47 4.5-2 4.5-2"/><path d="M12 15v5s3.03-.55 4.5-2c1.47-1.47 2-4.5 2-4.5"/></svg>',
    book: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    docker: '<svg viewBox="0 0 640 640" class="tree-svg-icon"><path fill="currentColor" d="M349.9 300.3L283.8 300.3L283.8 240.9L349.9 240.9L349.9 300.3zM349.9 96L283.8 96L283.8 156.7L349.9 156.7L349.9 96zM428.1 240.8L362 240.8L362 300.2L428.1 300.2L428.1 240.8zM271.8 168.7L205.7 168.7L205.7 228.8L271.8 228.8L271.8 168.7zM349.9 168.7L283.8 168.7L283.8 228.8L349.9 228.8L349.9 168.7zM626.7 268.7C612.3 259 579.1 255.5 553.6 260.3C550.3 236.3 536.9 215.4 512.5 196.6L498.5 187.3L489.2 201.3C470.8 229.1 465.8 274.9 485.5 305.1C476.8 309.8 459.7 316.2 437.1 315.8L2.4 315.8C-6.3 366.6 8.2 432.6 46.4 477.9C83.5 521.8 139.1 544.1 211.8 544.1C369.2 544.1 485.7 471.6 540.2 339.9C561.6 340.3 607.8 340 631.5 294.7C633 292.2 638.1 281.5 640 277.6L626.7 268.7zM115.6 240.8L49.6 240.8L49.6 300.2L115.7 300.2L115.7 240.8L115.6 240.8zM193.7 240.8L127.6 240.8L127.6 300.2L193.7 300.2L193.7 240.8zM271.8 240.8L205.7 240.8L205.7 300.2L271.8 300.2L271.8 240.8zM193.7 168.7L127.6 168.7L127.6 228.8L193.7 228.8L193.7 168.7z"/></svg>',
    workflow: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><circle cx="5" cy="6" r="3"/><circle cx="19" cy="6" r="3"/><circle cx="12" cy="18" r="3"/><path d="M5 9v3a3 3 0 0 0 3 3h0"/><path d="M19 9v3a3 3 0 0 1-3 3h0"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
    kanban: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="11" rx="1"/><rect x="17" y="3" width="5" height="14" rx="1"/></svg>',
    robot: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><rect x="3" y="8" width="18" height="12" rx="2"/><circle cx="9" cy="14" r="1.5"/><circle cx="15" cy="14" r="1.5"/><path d="M12 2v4"/><path d="M8 4h8"/><path d="M9 18v2"/><path d="M15 18v2"/></svg>',
    truck: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
    cloud: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/></svg>',
    code: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
    terminal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>',
    calendar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    chart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    users: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    mail: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
    server: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>',
    home: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
    shield: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
    link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
    bell: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="tree-svg-icon"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
    linux: '<svg viewBox="0 0 640 640" class="tree-svg-icon"><path fill="currentColor" d="M316.9 187.3C317.9 187.8 318.7 189 319.9 189C321 189 322.7 188.6 322.8 187.5C323 186.1 320.9 185.2 319.6 184.6C317.9 183.9 315.7 183.6 314.1 184.5C313.7 184.7 313.3 185.2 313.5 185.6C313.8 186.9 315.8 186.7 316.9 187.3zM295 189C296.2 189 297 187.8 298 187.3C299.1 186.7 301.1 186.9 301.5 185.7C301.7 185.3 301.3 184.8 300.9 184.6C299.3 183.7 297.1 184 295.4 184.7C294.1 185.3 292 186.2 292.2 187.6C292.3 188.6 294 189.1 295 189zM516 467.8C512.4 463.8 510.7 456.2 508.8 448.1C507 440 504.9 431.3 498.3 425.7C497 424.6 495.7 423.6 494.3 422.8C493 422 491.6 421.3 490.2 420.8C499.4 393.5 495.8 366.3 486.5 341.7C475.1 311.6 455.2 285.3 440 267.3C422.9 245.8 406.3 225.4 406.6 195.3C407.1 149.4 411.7 64.1 330.8 64C228.4 63.8 254 167.4 252.9 199.2C251.2 222.6 246.5 241 230.4 263.9C211.5 286.4 184.9 322.7 172.3 360.6C166.3 378.5 163.5 396.7 166.1 413.9C159.6 419.7 154.7 428.6 149.5 434.1C145.3 438.4 139.2 440 132.5 442.4C125.8 444.8 118.5 448.4 114 456.9C111.9 460.8 111.2 465 111.2 469.3C111.2 473.2 111.8 477.2 112.4 481.1C113.6 489.2 114.9 496.8 113.2 501.9C108 516.3 107.3 526.3 111 533.6C114.8 540.9 122.4 544.1 131.1 545.9C148.4 549.5 171.9 548.6 190.4 558.4C210.2 568.8 230.3 572.5 246.3 568.8C257.9 566.2 267.4 559.2 272.2 548.6C284.7 548.5 298.5 543.2 320.5 542C335.4 540.8 354.1 547.3 375.6 546.1C376.2 548.4 377 550.7 378.1 552.8L378.1 552.9C386.4 569.6 401.9 577.2 418.4 575.9C435 574.6 452.5 564.9 466.7 548C480.3 531.6 502.7 524.8 517.6 515.8C525 511.3 531 505.7 531.5 497.5C531.9 489.3 527.1 480.2 516 467.8zM319.8 151.3C329.6 129.1 354 129.5 363.8 150.9C370.3 165.1 367.4 181.8 359.5 191.3C357.9 190.5 353.6 188.7 346.9 186.4C348 185.2 350 183.7 350.8 181.8C355.6 170 350.6 154.8 341.7 154.5C334.4 154 327.8 165.3 329.9 177.5C325.8 175.5 320.5 174 316.9 173.1C315.9 166.2 316.6 158.5 319.8 151.3zM279.1 139.8C289.2 139.8 299.9 154 298.2 173.3C294.7 174.3 291.1 175.8 288 177.9C289.2 169 284.7 157.8 278.4 158.3C270 159 268.6 179.5 276.6 186.4C277.6 187.2 278.5 186.2 270.7 191.9C255.1 177.3 260.2 139.8 279.1 139.8zM265.5 200.5C271.7 195.9 279.1 190.5 279.6 190C284.3 185.6 293.1 175.8 307.5 175.8C314.6 175.8 323.1 178.1 333.4 184.7C339.7 188.8 344.7 189.1 356 194C364.4 197.5 369.7 203.7 366.5 212.2C363.9 219.3 355.5 226.6 343.8 230.3C332.7 233.9 324 246.3 305.6 245.2C301.7 245 298.6 244.2 296 243.1C288 239.6 283.8 232.7 276 228.1C267.4 223.3 262.8 217.7 261.3 212.8C259.9 207.9 261.3 203.8 265.5 200.5zM268.8 534.5C266.1 569.6 224.9 568.9 193.5 552.5C163.6 536.7 124.9 546 117 530.6C114.6 525.9 114.6 517.9 119.6 504.2L119.6 504C122 496.4 120.2 488 119 480.1C117.8 472.3 117.2 465.1 119.9 460.1C123.4 453.4 128.4 451 134.7 448.8C145 445.1 146.5 445.4 154.3 438.9C159.8 433.2 163.8 426 168.6 420.9C173.7 415.4 178.6 412.8 186.3 414C194.4 415.2 201.4 420.8 208.2 430L227.8 465.6C237.3 485.5 270.9 514 268.8 534.5zM267.4 508.6C263.3 502 257.8 495 253 489C260.1 489 267.2 486.8 269.7 480.1C272 473.9 269.7 465.2 262.3 455.2C248.8 437 224 422.7 224 422.7C210.5 414.3 202.9 404 199.4 392.8C195.9 381.6 196.4 369.5 199.1 357.6C204.3 334.7 217.7 312.4 226.3 298.4C228.6 296.7 227.1 301.6 217.6 319.2C209.1 335.3 193.2 372.5 215 401.6C215.6 380.9 220.5 359.8 228.8 340.1C240.8 312.7 266.1 265.2 268.1 227.4C269.2 228.2 272.7 230.6 274.3 231.5C278.9 234.2 282.4 238.2 286.9 241.8C299.3 251.8 315.4 251 329.3 243C335.5 239.5 340.5 235.5 345.2 234C355.1 230.9 363 225.4 367.5 219C375.2 249.4 393.2 293.3 404.7 314.7C410.8 326.1 423 350.2 428.3 379.3C431.6 379.2 435.3 379.7 439.2 380.7C453 345 427.5 306.5 415.9 295.8C411.2 291.2 411 289.2 413.3 289.3C425.9 300.5 442.5 323 448.5 348.3C451.3 359.9 451.8 372 448.9 384C465.3 390.8 484.8 401.9 479.6 418.8C477.4 418.7 476.4 418.8 475.4 418.8C478.6 408.7 471.5 401.2 452.6 392.7C433 384.1 416.6 384.1 414.3 405.2C402.2 409.4 396 419.9 392.9 432.5C390.1 443.7 389.3 457.2 388.5 472.4C388 480.1 384.9 490.4 381.7 501.4C349.6 524.3 305 534.3 267.4 508.6zM524.8 497.1C523.9 513.9 483.6 517 461.6 543.6C448.4 559.3 432.2 568 418 569.1C403.8 570.2 391.5 564.3 384.3 549.8C379.6 538.7 381.9 526.7 385.4 513.5C389.1 499.3 394.6 484.7 395.3 472.9C396.1 457.7 397 444.4 399.5 434.2C402.1 423.9 406.1 417 413.2 413.1C413.5 412.9 413.9 412.8 414.2 412.6C415 425.8 421.5 439.2 433 442.1C445.6 445.4 463.7 434.6 471.4 425.8C480.4 425.5 487.1 424.9 494 430.9C503.9 439.4 501.1 461.2 511.1 472.5C521.7 484.1 525.1 492 524.8 497.1zM269.4 212.7C271.4 214.6 274.1 217.2 277.4 219.8C284 225 293.2 230.4 304.7 230.4C316.3 230.4 327.2 224.5 336.5 219.6C341.4 217 347.4 212.6 351.3 209.2C355.2 205.8 357.2 202.9 354.4 202.6C351.6 202.3 351.8 205.2 348.4 207.7C344 210.9 338.7 215.1 334.5 217.5C327.1 221.7 315 227.7 304.6 227.7C294.2 227.7 285.9 222.9 279.7 218C276.6 215.5 274 213 272 211.1C270.5 209.7 270.1 206.5 267.7 206.2C266.3 206.1 265.9 209.9 269.4 212.7z"/></svg>',
    windows: '<svg viewBox="0 0 24 24" fill="currentColor" class="tree-svg-icon"><path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801"/></svg>',
    android: '<svg viewBox="0 0 24 24" fill="currentColor" class="tree-svg-icon"><path d="M6 18c0 .55.45 1 1 1h1v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h2v3.5c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5V19h1c.55 0 1-.45 1-1V8H6v10zM3.5 8C2.67 8 2 8.67 2 9.5v7c0 .83.67 1.5 1.5 1.5S5 17.33 5 16.5v-7C5 8.67 4.33 8 3.5 8zm17 0c-.83 0-1.5.67-1.5 1.5v7c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-7c0-.83-.67-1.5-1.5-1.5zm-4.97-5.84l1.3-1.3c.2-.2.2-.51 0-.71-.2-.2-.51-.2-.71 0l-1.48 1.48A5.84 5.84 0 0012 1c-.96 0-1.86.23-2.66.63L7.85.15c-.2-.2-.51-.2-.71 0-.2.2-.2.51 0 .71l1.31 1.31A5.983 5.983 0 006 7h12c0-2.21-1.2-4.15-2.97-5.16zM10 5H9V4h1v1zm5 0h-1V4h1v1z"/></svg>',
    apple: '<svg viewBox="0 0 24 24" fill="currentColor" class="tree-svg-icon"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>'
  };

  /* ─── INIT ──────────────────────────────────────────────────── */

  function init() {
    applyViewMode(VIEW_MODE, false);
    bindEvents();
    var tableEl = document.getElementById('approvalsTable');
    if (tableEl && typeof TableSort !== 'undefined') {
      TableSort.init(tableEl, {
        onSort: function () {
          renderTable();
        }
      });
    }
    loadApprovals();
  }

  function bindEvents() {
    var searchInput = document.getElementById('approvalsSearch');
    if (searchInput) {
      searchInput.addEventListener('input', function () {
        clearTimeout(SEARCH_TIMER);
        SEARCH_TIMER = setTimeout(loadApprovals, 250);
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closePreviewModal();
        closeRejectModal();
      }
    });
  }

  /* ─── VIEW MODE TOGGLE ──────────────────────────────────────── */

  function setViewMode(mode) {
    VIEW_MODE = mode;
    localStorage.setItem('approvals_view', mode);
    applyViewMode(mode, true);
  }

  function applyViewMode(mode, rerender) {
    var btnCard = document.getElementById('approvalsBtnCard');
    var btnList = document.getElementById('approvalsBtnList');
    var grid = document.getElementById('approvalsGrid');
    var tableWrapper = document.getElementById('approvalsTableWrapper');

    if (btnCard) btnCard.classList.toggle('active', mode === 'card');
    if (btnList) btnList.classList.toggle('active', mode === 'list');

    if (mode === 'list') {
      if (grid) grid.hidden = true;
      if (tableWrapper) tableWrapper.hidden = false;
    } else {
      if (grid) grid.hidden = false;
      if (tableWrapper) tableWrapper.hidden = true;
    }

    if (rerender) renderView();
  }

  /* ─── TABS & STATUS FILTER ──────────────────────────────────── */

  function setTab(status, el) {
    CURRENT_STATUS = status;
    var tabs = document.querySelectorAll('.approval-tab');
    tabs.forEach(function (tab) {
      tab.classList.toggle('active', tab.getAttribute('data-status') === status);
    });
    var statCards = document.querySelectorAll('.approval-stat-card');
    statCards.forEach(function (card) { card.classList.remove('active'); });
    loadApprovals();
  }

  function filterStatus(status, el) {
    CURRENT_STATUS = status;
    var statCards = document.querySelectorAll('.approval-stat-card');
    statCards.forEach(function (card) {
      card.classList.toggle('active', card === el);
    });
    var tabs = document.querySelectorAll('.approval-tab');
    tabs.forEach(function (tab) {
      tab.classList.toggle('active', tab.getAttribute('data-status') === status);
    });
    loadApprovals();
  }

  /* ─── DATA LOADING ──────────────────────────────────────────── */

  function loadApprovals() {
    var q = (document.getElementById('approvalsSearch') ? document.getElementById('approvalsSearch').value : '').trim();
    var url = '/api/approvals?status=' + encodeURIComponent(CURRENT_STATUS) + '&q=' + encodeURIComponent(q);

    api('GET', url).then(function (res) {
      ALL_DOCS = res.docs || [];
      STATS = res.stats || {};
      updateCounters();
      renderView();
    }).catch(function (err) {
      toast(err.message || 'Erro ao carregar aprovações.', 'error');
    });
  }

  function updateCounters() {
    var statPending  = document.getElementById('statPendingCount');
    var statApproved = document.getElementById('statApprovedCount');
    var statRejected = document.getElementById('statRejectedCount');
    var statTotal    = document.getElementById('statTotalCount');
    var tabBadge     = document.getElementById('tabBadgePending');

    if (statPending)  statPending.textContent  = STATS.total_pending  || 0;
    if (statApproved) statApproved.textContent = STATS.total_approved || 0;
    if (statRejected) statRejected.textContent = STATS.total_rejected || 0;
    if (statTotal)    statTotal.textContent    = STATS.total_docs     || 0;
    if (tabBadge)     tabBadge.textContent     = STATS.total_pending  || 0;
  }

  /* ─── RENDER DISPATCHER ─────────────────────────────────────── */

  function renderView() {
    var emptyState = document.getElementById('approvalsEmptyState');
    var grid = document.getElementById('approvalsGrid');
    var tableWrapper = document.getElementById('approvalsTableWrapper');
    if (!grid) return;

    if (!ALL_DOCS.length) {
      grid.innerHTML = '';
      grid.hidden = true;
      if (tableWrapper) tableWrapper.hidden = true;
      if (emptyState) emptyState.hidden = false;
      return;
    }
    if (emptyState) emptyState.hidden = true;

    if (VIEW_MODE === 'list') {
      grid.hidden = true;
      if (tableWrapper) tableWrapper.hidden = false;
      renderTable();
    } else {
      if (tableWrapper) tableWrapper.hidden = true;
      grid.hidden = false;
      renderCards(grid);
    }
  }

  /* ─── HELPERS: badges & actions ─────────────────────────────── */

  function buildStatusBadge(status) {
    if (status === 'aprovado') {
      return '<span class="approval-badge approved"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><polyline points="20 6 9 17 4 12"/></svg>Publicado</span>';
    }
    if (status === 'rejeitado') {
      return '<span class="approval-badge rejected"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Rejeitado</span>';
    }
    return '<span class="approval-badge pending"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px;margin-right:2px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Aguardando</span>';
  }

  function buildActionsHtml(d) {
    if (d.status === 'pendente') {
      return '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Approvals.previewDoc(' + d.id + ')" title="Revisar documento">'
        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>'
        + '<button type="button" class="table-action-btn" style="color:var(--danger,#d63939)" onclick="event.stopPropagation();Approvals.openRejectModal(' + d.id + ')" title="Rejeitar">'
        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>'
        + '<button type="button" class="table-action-btn" style="color:var(--docs-primary,#1abb9c)" onclick="event.stopPropagation();Approvals.approveDoc(' + d.id + ')" title="Aprovar e publicar">'
        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12"/></svg></button>';
    }
    var html = '<button type="button" class="table-action-btn" onclick="event.stopPropagation();Approvals.previewDoc(' + d.id + ')" title="Visualizar / Revisar">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>';
    if (d.status === 'rejeitado') {
      html += '<button type="button" class="table-action-btn" style="color:var(--docs-primary,#1abb9c)" onclick="event.stopPropagation();Approvals.approveDoc(' + d.id + ')" title="Reavaliar e Aprovar">'
        + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><polyline points="20 6 9 17 4 12"/></svg></button>';
    }
    return html;
  }

  /* ─── RENDER: CARDS ─────────────────────────────────────────── */

  function renderCards(grid) {
    var html = '';
    for (var i = 0; i < ALL_DOCS.length; i++) {
      var d = ALL_DOCS[i];
      var catIcon = d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : ICONS_MAP['folder'];
      var catBadge = d.category_name
        ? '<span class="doc-category-badge">' + catIcon + ' ' + esc(d.category_name) + '</span>'
        : '<span class="doc-category-badge" style="background:var(--docs-surface-2);color:var(--docs-text-mut)">' + ICONS_MAP['folder'] + ' Geral</span>';

      var cleanPreview = stripMarkdown(d.content || '').substring(0, 160);

      var versionHtml = d.version_count > 0
        ? '<span style="font-size:0.7rem;color:var(--docs-text-mut);background:var(--docs-surface-2);padding:1px 6px;border-radius:4px;">v' + d.version_count + '</span>'
        : '';

      var rejectionHtml = '';
      if (d.status === 'rejeitado' && d.rejection_reason) {
        rejectionHtml = '<div class="approval-rejection-callout">'
          + '<strong>Motivo da rejeição:</strong> ' + esc(d.rejection_reason)
          + '</div>';
      }

      html += '<div class="approval-card-item">'
        + '<div class="card-top">'
        + catBadge
        + buildStatusBadge(d.status)
        + '</div>'
        + '<h3 class="card-title">' + esc(d.title) + '</h3>'
        + '<div class="card-preview">' + (cleanPreview ? esc(cleanPreview) : '<em>Documento sem conteúdo</em>') + '</div>'
        + rejectionHtml
        + '<div class="card-footer-info">'
        + '<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:2px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Autor: <strong>' + esc(d.author_name || 'Desconhecido') + '</strong></span>'
        + '<span>🕒 Enviado em: ' + formatDate(d.updated_at) + '</span>'
        + versionHtml
        + (d.status === 'rejeitado' && d.rejected_by_name
            ? '<span style="color:var(--danger,#d63939)">↩ Rejeitado por: ' + esc(d.rejected_by_name) + '</span>'
            : (d.approved_by_name ? '<span>🛡️ Aprovado por: ' + esc(d.approved_by_name) + '</span>' : ''))
        + '</div>'
        + '<div class="card-actions-bar">'
        + buildActionsHtml(d)
        + '</div>'
        + '</div>';
    }
    grid.innerHTML = html;
  }

  /* ─── RENDER: TABLE (LIST VIEW) ─────────────────────────────── */

  function renderTable() {
    var tbody = document.getElementById('approvalsTableBody');
    if (!tbody) return;

    var docsToRender = ALL_DOCS.slice();

    // Ordenação com TableSort se ativa
    if (typeof TableSort !== 'undefined' && TableSort.getSort) {
      var sortState = TableSort.getSort('approvalsTable');
      if (sortState && sortState.col) {
        docsToRender = TableSort.sortArray(docsToRender, sortState.col, sortState.dir, function (d, col) {
          if (col === 'title') return d.title || '';
          if (col === 'category') return d.category_name || '';
          if (col === 'author') return d.author_name || '';
          if (col === 'status') return d.status || '';
          if (col === 'updated') return d.updated_at || '';
          return d.id;
        });
      }
    }

    var totalItems = docsToRender.length;
    var totalPages = Math.ceil(totalItems / PER_PAGE) || 1;
    if (CURRENT_PAGE > totalPages) CURRENT_PAGE = totalPages;
    if (CURRENT_PAGE < 1) CURRENT_PAGE = 1;

    var startIndex = (CURRENT_PAGE - 1) * PER_PAGE;
    var endIndex = Math.min(startIndex + PER_PAGE, totalItems);
    var pageDocs = docsToRender.slice(startIndex, endIndex);

    var html = '';
    for (var i = 0; i < pageDocs.length; i++) {
      var d = pageDocs[i];
      var catIcon = d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : ICONS_MAP['folder'];
      var catColor = d.category_color || 'var(--docs-primary, #1abb9c)';
      var catHtml = d.category_name
        ? '<span class="doc-category-badge" style="font-size:0.75rem;padding:0.18rem 0.55rem;border-left:3px solid ' + esc(catColor) + ';"><span style="color:' + esc(catColor) + '">' + catIcon + '</span> ' + esc(d.category_name) + '</span>'
        : '<span style="color:var(--docs-text-mut);font-size:0.75rem">—</span>';

      var rejNote = (d.status === 'rejeitado' && d.rejection_reason)
        ? '<div style="font-size:0.7rem;color:var(--danger,#d63939);margin-top:2px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + esc(d.rejection_reason) + '">↩ ' + esc(d.rejection_reason) + '</div>'
        : '';

      var docIcon = '<span style="color:' + esc(catColor) + '">' + (d.category_icon && ICONS_MAP[d.category_icon] ? ICONS_MAP[d.category_icon] : ICONS_MAP['doc']) + '</span>';

      html += '<tr>'
        + '<td style="width: 40px; text-align: center;"><span style="font-size:1.1rem;display:inline-flex;align-items:center;">' + docIcon + '</span></td>'
        + '<td>'
        + '<div class="table-doc-title" onclick="Approvals.previewDoc(' + d.id + ')" style="cursor:pointer;font-weight:600;color:var(--docs-text);" title="Clique para revisar ' + esc(d.title) + '">'
        + esc(d.title)
        + rejNote
        + '</div>'
        + '</td>'
        + '<td>' + catHtml + '</td>'
        + '<td><span style="font-size:0.78rem;color:var(--docs-text-mut);font-weight:500;display:inline-flex;align-items:center;gap:4px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>' + esc(d.author_name || 'Admin') + '</span></td>'
        + '<td>' + buildStatusBadge(d.status) + (d.version_count > 0 ? ' <span style="font-size:0.7rem;color:var(--docs-text-mut);margin-left:2px;">v' + d.version_count + '</span>' : '') + '</td>'
        + '<td><span style="font-size:0.8rem;color:var(--docs-text-mut);white-space:nowrap">' + formatDate(d.updated_at) + '</span></td>'
        + '<td style="text-align:right;white-space:nowrap">'
        + '<div class="table-actions">'
        + buildActionsHtml(d)
        + '</div>'
        + '</td>'
        + '</tr>';
    }

    tbody.innerHTML = html;

    if (typeof renderPagination === 'function') {
      renderPagination('approvalsPagination', totalItems, CURRENT_PAGE, totalPages, function (p) {
        CURRENT_PAGE = p;
        renderTable();
      });
    }
  }

  /* ─── MODAL: PREVIEW E COMPARAÇÃO DE VERSÕES (GIT DIFF) ──────── */

  var CURRENT_PREVIEW_TAB = 'diff';

  function computeLineDiff(oldText, newText) {
    var oldLines = (oldText || '').replace(/\r\n/g, '\n').split('\n');
    var newLines = (newText || '').replace(/\r\n/g, '\n').split('\n');

    var M = oldLines.length;
    var N = newLines.length;

    if (oldText === newText) {
      var same = [];
      for (var k = 0; k < M; k++) {
        same.push({ type: 'same', oldLine: oldLines[k], newLine: newLines[k], oldNum: k + 1, newNum: k + 1 });
      }
      return same;
    }

    var dp = [];
    for (var i = 0; i <= M; i++) {
      dp.push(new Int32Array(N + 1));
    }

    for (var i = 1; i <= M; i++) {
      for (var j = 1; j <= N; j++) {
        if (oldLines[i - 1] === newLines[j - 1]) {
          dp[i][j] = dp[i - 1][j - 1] + 1;
        } else {
          dp[i][j] = dp[i - 1][j] > dp[i][j - 1] ? dp[i - 1][j] : dp[i][j - 1];
        }
      }
    }

    var i = M, j = N;
    var ops = [];
    while (i > 0 || j > 0) {
      if (i > 0 && j > 0 && oldLines[i - 1] === newLines[j - 1]) {
        ops.unshift({ type: 'same', oldLine: oldLines[i - 1], newLine: newLines[j - 1], oldNum: i, newNum: j });
        i--; j--;
      } else if (j > 0 && (i === 0 || dp[i][j - 1] >= dp[i - 1][j])) {
        ops.unshift({ type: 'add', newLine: newLines[j - 1], newNum: j });
        j--;
      } else {
        ops.unshift({ type: 'delete', oldLine: oldLines[i - 1], oldNum: i });
        i--;
      }
    }

    return ops;
  }

  function renderSideBySideDiff(ops, oldVersionLabel, newVersionLabel) {
    var leftRows = [];
    var rightRows = [];
    var addCount = 0;
    var delCount = 0;

    var k = 0;
    while (k < ops.length) {
      var op = ops[k];
      if (op.type === 'same') {
        leftRows.push({ num: op.oldNum, text: op.oldLine, type: 'same' });
        rightRows.push({ num: op.newNum, text: op.newLine, type: 'same' });
        k++;
      } else if (op.type === 'delete') {
        if (k + 1 < ops.length && ops[k + 1].type === 'add') {
          var nextAdd = ops[k + 1];
          leftRows.push({ num: op.oldNum, text: op.oldLine, type: 'delete' });
          rightRows.push({ num: nextAdd.newNum, text: nextAdd.newLine, type: 'add' });
          delCount++;
          addCount++;
          k += 2;
        } else {
          leftRows.push({ num: op.oldNum, text: op.oldLine, type: 'delete' });
          rightRows.push({ num: '', text: '', type: 'empty' });
          delCount++;
          k++;
        }
      } else if (op.type === 'add') {
        leftRows.push({ num: '', text: '', type: 'empty' });
        rightRows.push({ num: op.newNum, text: op.newLine, type: 'add' });
        addCount++;
        k++;
      }
    }

    var leftHtml = '';
    var rightHtml = '';

    for (var r = 0; r < leftRows.length; r++) {
      var l = leftRows[r];
      var rg = rightRows[r];

      var lClass = l.type === 'delete' ? 'diff-line-delete' : (l.type === 'empty' ? 'diff-line-empty' : 'diff-line-same');
      var lSymbol = l.type === 'delete' ? '-' : ' ';
      leftHtml += '<tr class="' + lClass + '">'
        + '<td class="diff-line-num">' + (l.num || '') + '</td>'
        + '<td class="diff-line-symbol">' + lSymbol + '</td>'
        + '<td class="diff-line-content">' + esc(l.text) + '</td>'
        + '</tr>';

      var rClass = rg.type === 'add' ? 'diff-line-add' : (rg.type === 'empty' ? 'diff-line-empty' : 'diff-line-same');
      var rSymbol = rg.type === 'add' ? '+' : ' ';
      rightHtml += '<tr class="' + rClass + '">'
        + '<td class="diff-line-num">' + (rg.num || '') + '</td>'
        + '<td class="diff-line-symbol">' + rSymbol + '</td>'
        + '<td class="diff-line-content">' + esc(rg.text) + '</td>'
        + '</tr>';
    }

    var statsHtml = '<div style="display:flex;align-items:center;gap:0.5rem;font-size:0.78rem;font-weight:600;">'
      + '<span style="color:#166534;background:#dcfce7;padding:3px 9px;border-radius:12px;">+' + addCount + ' adições</span>'
      + '<span style="color:#991b1b;background:#fee2e2;padding:3px 9px;border-radius:12px;">-' + delCount + ' remoções</span>'
      + '</div>';

    var containerHtml = '<div class="diff-wrapper">'
      + '<div class="diff-grid">'
      + '<div class="diff-pane diff-pane-left">'
      + '<div class="diff-pane-header"><span>◀ ' + esc(oldVersionLabel) + '</span></div>'
      + '<table class="diff-table"><tbody>' + leftHtml + '</tbody></table>'
      + '</div>'
      + '<div class="diff-pane diff-pane-right">'
      + '<div class="diff-pane-header"><span>▶ ' + esc(newVersionLabel) + '</span></div>'
      + '<table class="diff-table"><tbody>' + rightHtml + '</tbody></table>'
      + '</div>'
      + '</div>'
      + '</div>';

    return {
      html: containerHtml,
      statsHtml: statsHtml
    };
  }

  function switchPreviewTab(tab) {
    DocDiff.switchTab(tab);
  }

  function onVersionSelectChange() {
    DocDiff.onVersionSelectChange();
  }

  function compareVersionIndex(idx) {
    DocDiff.compareVersionIndex(idx);
  }

  function previewDoc(id) {
    DocDiff.open(id, { showApprovalButtons: true });
  }

  function closePreviewModal() {
    DocDiff.close();
  }

  /* ─── APPROVE / REJECT ──────────────────────────────────────── */

  function approveDoc(id) {
    showConfirm({
      title: 'Aprovar e Publicar Documento?',
      message: 'O documento será publicado imediatamente e ficará visível para todos os usuários com acesso de leitura.',
      confirmText: 'Aprovar e Publicar',
      confirmClass: 'btn btn-primary'
    }, function () {
      api('POST', '/api/approvals/' + id + '/approve', {}).then(function () {
        toast('Documento aprovado e publicado com sucesso!', 'success');
        closePreviewModal();
        loadApprovals();
      }).catch(function (err) {
        toast(err.message || 'Erro ao aprovar documento.', 'error');
      });
    });
  }

  function approveFromPreview() {
    var doc = DocDiff.getCurrentDoc();
    if (!doc) return;
    approveDoc(doc.id);
  }

  function openRejectModal(id) {
    CURRENT_REJECT_ID = id;
    var modal = document.getElementById('rejectReasonModal');
    var input = document.getElementById('rejectReasonText');
    if (input) input.value = '';
    if (modal) modal.hidden = false;
    if (input) input.focus();
  }

  function openRejectFromPreview() {
    var doc = DocDiff.getCurrentDoc();
    if (!doc) return;
    openRejectModal(doc.id);
  }

  function closeRejectModal() {
    var modal = document.getElementById('rejectReasonModal');
    if (modal) modal.hidden = true;
    CURRENT_REJECT_ID = null;
  }

  function confirmReject() {
    if (!CURRENT_REJECT_ID) return;
    var reason = (document.getElementById('rejectReasonText') ? document.getElementById('rejectReasonText').value : '').trim();
    if (!reason) {
      toast('Por favor, informe a justificativa da rejeição.', 'error');
      document.getElementById('rejectReasonText').focus();
      return;
    }

    api('POST', '/api/approvals/' + CURRENT_REJECT_ID + '/reject', { reason: reason }).then(function () {
      toast('Documento rejeitado e devolvido para ajustes.', 'info');
      closeRejectModal();
      closePreviewModal();
      loadApprovals();
    }).catch(function (err) {
      toast(err.message || 'Erro ao rejeitar documento.', 'error');
    });
  }

  /* ─── UTILS ─────────────────────────────────────────────────── */

  function esc(s) {
    if (s === null || s === undefined) return '';
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function stripMarkdown(md) {
    if (!md) return '';
    return md
      .replace(/!\[.*?\]\(.*?\)/g, '')
      .replace(/\[(.*?)\]\(.*?\)/g, '$1')
      .replace(/`{1,3}.*?`{1,3}/gs, '')
      .replace(/#{1,6}\s+/g, '')
      .replace(/(\*\*|__)(.*?)\1/g, '$2')
      .replace(/(\*|_)(.*?)\1/g, '$2')
      .replace(/~~(.*?)~~/g, '$1')
      .replace(/^\s*[-*+]\s+/gm, '')
      .replace(/^\s*\d+\.\s+/gm, '')
      .replace(/^\s*>\s+/gm, '')
      .replace(/\n+/g, ' ')
      .trim();
  }

  function parseMarkdown(md) {
    if (!md) return '';
    if (typeof window.parseMarkdown === 'function' && window.parseMarkdown !== parseMarkdown) {
      return window.parseMarkdown(md);
    }
    if (typeof window.marked === 'function') {
      return window.marked.parse(md);
    }
    if (window.marked && typeof window.marked.parse === 'function') {
      return window.marked.parse(md);
    }
    return esc(md).replace(/\n/g, '<br>');
  }

  function formatDate(d) {
    if (!d) return '—';
    try {
      var date = new Date(d.replace(' ', 'T'));
      if (isNaN(date.getTime())) return d;
      return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
        + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
      return d;
    }
  }

  return {
    init:                init,
    setViewMode:         setViewMode,
    setTab:              setTab,
    filterStatus:        filterStatus,
    previewDoc:          previewDoc,
    closePreviewModal:   closePreviewModal,
    approveDoc:          approveDoc,
    approveFromPreview:  approveFromPreview,
    openRejectModal:     openRejectModal,
    openRejectFromPreview: openRejectFromPreview,
    closeRejectModal:    closeRejectModal,
    confirmReject:       confirmReject,
    switchPreviewTab:    switchPreviewTab,
    onVersionSelectChange: onVersionSelectChange,
    compareVersionIndex: compareVersionIndex,
  };
})();

document.addEventListener('DOMContentLoaded', Approvals.init);
