/**
 * Gloy Skincare - Warm Organic 3D Skincare Engine
 * Soft warm lighting and interactive 360 Gloy Serum 3D bottle visualizer
 */
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('hero-3d-canvas');
  if (!container || typeof THREE === 'undefined') return;

  // 1. Scene & Camera Setup
  const scene = new THREE.Scene();
  
  const camera = new THREE.PerspectiveCamera(
    42,
    container.clientWidth / container.clientHeight,
    0.1,
    100
  );
  camera.position.set(0, 0, 7.5);

  // 2. High-Performance WebGL Renderer
  const renderer = new THREE.WebGLRenderer({
    alpha: true,
    antialias: true,
    powerPreference: 'high-performance'
  });
  renderer.setSize(container.clientWidth, container.clientHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = THREE.PCFSoftShadowMap;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.1;
  container.appendChild(renderer.domElement);

  // 3. Studio Lighting Rig (Soft Warm Daylight)
  const ambientLight = new THREE.AmbientLight(0xffffff, 1.8);
  scene.add(ambientLight);

  const keyLight = new THREE.DirectionalLight(0xfff8f0, 2.4); // Bright Warm Key
  keyLight.position.set(4, 6, 5);
  keyLight.castShadow = true;
  scene.add(keyLight);

  const fillLight = new THREE.DirectionalLight(0xf4efea, 1.4); // Warm Cream Fill
  fillLight.position.set(-5, -2, 3);
  scene.add(fillLight);

  const rimLight = new THREE.DirectionalLight(0x7c8b6f, 2.2); // Sage Green Rim Accent
  rimLight.position.set(0, 5, -6);
  scene.add(rimLight);

  // 4. Procedural Photorealistic 3D Glass Serum Bottle
  const bottleGroup = new THREE.Group();

  // Glass Outer Body (Physical Transmission Glass)
  const glassGeo = new THREE.CylinderGeometry(0.85, 0.85, 2.4, 64);
  const glassMat = new THREE.MeshPhysicalMaterial({
    color: 0xffffff,
    transmission: 0.95,
    opacity: 1,
    transparent: true,
    roughness: 0.05,
    metalness: 0,
    ior: 1.52,
    thickness: 0.6,
    clearcoat: 1.0,
    clearcoatRoughness: 0.05,
    reflectivity: 0.95
  });
  const glassMesh = new THREE.Mesh(glassGeo, glassMat);
  glassMesh.castShadow = true;
  bottleGroup.add(glassMesh);

  // Inner Liquid Core (Glutathione Serum Clear Liquid)
  const liquidGeo = new THREE.CylinderGeometry(0.76, 0.76, 2.05, 32);
  
  // Formula Color Presets
  const formulaColors = {
    radiance: { color: 0x38BDF8, roughness: 0.12 }, // Gloy Glutathione Light Turquoise
    rosehip:  { color: 0x7C8B6F, roughness: 0.15 }, // Gloy Botanical Sage
    peptide:  { color: 0xC97C5D, roughness: 0.2 }   // Gloy Terracotta Clay
  };

  const liquidMat = new THREE.MeshStandardMaterial({
    color: formulaColors.radiance.color,
    roughness: formulaColors.radiance.roughness,
    metalness: 0.1,
    opacity: 0.85,
    transparent: true
  });
  const liquidMesh = new THREE.Mesh(liquidGeo, liquidMat);
  liquidMesh.position.y = -0.05;
  bottleGroup.add(liquidMesh);

  // Metallic Champagne Gold Collar
  const collarGeo = new THREE.CylinderGeometry(0.42, 0.46, 0.32, 32);
  const goldMat = new THREE.MeshStandardMaterial({
    color: 0xD4AF37,
    roughness: 0.18,
    metalness: 0.92
  });
  const collarMesh = new THREE.Mesh(collarGeo, goldMat);
  collarMesh.position.y = 1.35;
  collarMesh.castShadow = true;
  bottleGroup.add(collarMesh);

  // Crisp White Cap
  const capGeo = new THREE.CylinderGeometry(0.34, 0.38, 0.48, 32);
  const capMat = new THREE.MeshStandardMaterial({
    color: 0xFFFFFF,
    roughness: 0.25,
    metalness: 0.1
  });
  const capMesh = new THREE.Mesh(capGeo, capMat);
  capMesh.position.y = 1.72;
  capMesh.castShadow = true;
  bottleGroup.add(capMesh);

  // Pipette Rubber Bulb
  const bulbGeo = new THREE.SphereGeometry(0.28, 32, 16);
  bulbGeo.scale(1, 1.25, 1);
  const bulbMesh = new THREE.Mesh(bulbGeo, capMat);
  bulbMesh.position.y = 2.12;
  bottleGroup.add(bulbMesh);

  // Load Real Gloy Serum Packaging Texture for 3D Label
  const textureLoader = new THREE.TextureLoader();
  const labelGeo = new THREE.CylinderGeometry(0.86, 0.86, 1.4, 64, 1, true, -Math.PI * 0.45, Math.PI * 0.9);
  
  let labelMat = new THREE.MeshStandardMaterial({
    color: 0x38BDF8,
    roughness: 0.45,
    side: THREE.DoubleSide
  });

  textureLoader.load('assets/images/gloy_serum_raw.png', (texture) => {
    labelMat.map = texture;
    labelMat.color.setHex(0xffffff);
    labelMat.needsUpdate = true;
  });

  const labelMesh = new THREE.Mesh(labelGeo, labelMat);
  labelMesh.position.y = -0.1;
  bottleGroup.add(labelMesh);

  // Soft Ground Shadow Plane
  const shadowGeo = new THREE.PlaneGeometry(6, 6);
  const shadowMat = new THREE.ShadowMaterial({ opacity: 0.12 });
  const shadowPlane = new THREE.Mesh(shadowGeo, shadowMat);
  shadowPlane.rotation.x = -Math.PI / 2;
  shadowPlane.position.y = -2.2;
  shadowPlane.receiveShadow = true;
  scene.add(shadowPlane);

  // INITIAL ENTRANCE POSITION
  bottleGroup.position.set(0.1, 7.0, 0);
  bottleGroup.rotation.y = -0.3;
  scene.add(bottleGroup);

  // 5. GSAP ENTRANCE ANIMATION
  let entranceComplete = false;

  if (typeof gsap !== 'undefined') {
    gsap.to(bottleGroup.position, {
      y: 0,
      duration: 1.6,
      ease: "power3.out",
      onComplete: () => {
        entranceComplete = true;
      }
    });

    gsap.fromTo(bottleGroup.rotation, 
      { y: -2.2, x: 0.5 },
      { y: -0.3, x: 0, duration: 1.6, ease: "power3.out" }
    );
  } else {
    bottleGroup.position.y = 0;
    entranceComplete = true;
  }

  // Trigger HTML Text Entrance
  setTimeout(() => {
    document.querySelectorAll('.hero-anim-left').forEach(el => el.classList.add('is-active'));
    document.querySelectorAll('.hero-anim-right').forEach(el => el.classList.add('is-active'));
  }, 150);

  // =========================================================================
  // 6. INTERACTIVE 360° MANUAL ROTATION
  // =========================================================================
  let isDragging = false;
  let previousMouseX = 0;
  let previousMouseY = 0;
  let dragRotationX = 0;
  let dragRotationY = -0.3;

  // Manual Drag Rotation on 3D Canvas
  container.addEventListener('mousedown', (e) => {
    isDragging = true;
    previousMouseX = e.clientX;
    previousMouseY = e.clientY;
  });

  window.addEventListener('mouseup', () => {
    isDragging = false;
  });

  window.addEventListener('mousemove', (e) => {
    if (isDragging) {
      const deltaX = e.clientX - previousMouseX;
      const deltaY = e.clientY - previousMouseY;
      dragRotationY += deltaX * 0.008;
      dragRotationX += deltaY * 0.008;
      previousMouseX = e.clientX;
      previousMouseY = e.clientY;
    }
  });

  // Touch Drag Support for Mobile
  container.addEventListener('touchstart', (e) => {
    if (e.touches.length === 1) {
      isDragging = true;
      previousMouseX = e.touches[0].clientX;
      previousMouseY = e.touches[0].clientY;
    }
  });

  window.addEventListener('touchmove', (e) => {
    if (isDragging && e.touches.length === 1) {
      const deltaX = e.touches[0].clientX - previousMouseX;
      const deltaY = e.touches[0].clientY - previousMouseY;
      dragRotationY += deltaX * 0.008;
      dragRotationX += deltaY * 0.008;
      previousMouseX = e.touches[0].clientX;
      previousMouseY = e.touches[0].clientY;
    }
  });

  window.addEventListener('touchend', () => {
    isDragging = false;
  });

  // 7. Scroll Reaction
  let scrollY = 0;
  window.addEventListener('scroll', () => {
    scrollY = window.scrollY;
  });

  // 8. Global API to Change Formula Color & Active Button State
  window.setBottleFormula = function(formulaKey) {
    if (formulaColors[formulaKey]) {
      const target = formulaColors[formulaKey];
      if (typeof gsap !== 'undefined') {
        gsap.to(liquidMat.color, {
          r: new THREE.Color(target.color).r,
          g: new THREE.Color(target.color).g,
          b: new THREE.Color(target.color).b,
          duration: 0.8
        });
      } else {
        liquidMat.color.setHex(target.color);
      }
    }
  };

  window.switchFormula = function(btnElement, formulaKey) {
    document.querySelectorAll('.formula-btn').forEach(btn => btn.classList.remove('active'));
    if (btnElement) btnElement.classList.add('active');
    window.setBottleFormula(formulaKey);
  };

  // 9. Main Render Loop
  const clock = new THREE.Clock();

  function animate() {
    requestAnimationFrame(animate);

    const elapsedTime = clock.getElapsedTime();

    // Natural smooth floating animation
    if (entranceComplete) {
      bottleGroup.position.y = Math.sin(elapsedTime * 1.5) * 0.12;
    }

    if (isDragging) {
      bottleGroup.rotation.y += (dragRotationY - bottleGroup.rotation.y) * 0.1;
      bottleGroup.rotation.x += (dragRotationX - bottleGroup.rotation.x) * 0.1;
    } else {
      bottleGroup.rotation.y += (dragRotationY - bottleGroup.rotation.y) * 0.05;
      bottleGroup.rotation.x += (dragRotationX - bottleGroup.rotation.x) * 0.05;
    }

    camera.position.z = 7.5 - Math.min(scrollY * 0.0015, 1.5);

    renderer.render(scene, camera);
  }

  animate();

  // 10. Window Resize Listener
  window.addEventListener('resize', () => {
    if (!container) return;
    camera.aspect = container.clientWidth / container.clientHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(container.clientWidth, container.clientHeight);
  });
});
