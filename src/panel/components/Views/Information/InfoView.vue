<template>
  <k-grid variant="columns">
    <!-- Configuration Info Section -->
    <k-column width="1/2">
      <k-simplestats-info-config path="configinfo" />
    </k-column>

    <!-- Database Info Section-->
    <k-column width="1/2">
      <k-simplestats-info-database />
    </k-column>

    <!-- Tracking Testers Section -->
    <k-column width="1/1">
      <k-line-field /><br />
      <k-simplestats-info-testers />
    </k-column>

    <!-- Visitors Info Section -->
    <k-column width="1/1">
      <k-line-field /><br />
      <k-simplestats-info-visitors path="visitors" />
    </k-column>
  </k-grid>
</template>
