---
name: new-entity
description: Scaffold a new Doctrine entity in src/Entity/ following this repo's established conventions (UUID id, asymmetric visibility, attribute mapping). Use when the user asks to add a new entity/model to the MUD domain.
---

Create the entity class in `src/Entity/<Name>.php` matching the style of the existing entities (`Account`, `Character`, `Item`, `Room`, `RoomExit`):

- Namespace `App\Entity`, class marked `#[ORM\Entity]`.
- Primary key: `#[ORM\Id]` + `#[ORM\GeneratedValue(strategy: 'CUSTOM')]` + `#[ORM\CustomIdGenerator(class: \Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator::class)]` + `#[ORM\Column(type: 'uuid', unique: true)]` on `private(set) \Symfony\Component\Uid\Uuid $id;` — no auto-increment ints.
- Use PHP asymmetric visibility (`private(set) <type> $prop;`) for every property instead of traditional `private` fields with getters/setters. Do not write getters/setters unless the user asks for derived/computed behavior beyond a plain property read.
- Map relations with Doctrine attributes (`#[ORM\ManyToOne]`, `#[ORM\OneToMany]`, `#[ORM\ManyToMany]`), specifying `targetEntity`, and `inversedBy`/`mappedBy` to keep both sides of a relation consistent (check the sibling entity being referenced and wire the inverse side too — don't leave one-sided relations, this repo already has one such mismatch between `Character` and `Item` worth avoiding in new code).
- Only add a `__construct()` if the entity owns any `Collection` properties (`OneToMany`/`ManyToMany`) — initialize each as `new ArrayCollection()`, following `Room`'s constructor. Entities with no collections (e.g. `Character`) have no constructor.
- No annotations, no XML/YAML mapping — attributes only.

After creating the entity, remind the user to generate a migration for it (`make console "doctrine:migrations:diff"`) and to review the generated file before applying it.
