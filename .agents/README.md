# Agents Directory

This directory contains repository-local agent definitions, prompts, policies, and supporting documents.

## Purpose

Keep agent-related assets separate from application code so they can evolve independently.

## Structure

- `quality/`
  - quality guard definitions
  - prompts
  - checklists
  - enforcement notes

## Rule

Agent assets in this directory must support `diff-scoped` review and must not target untouched legacy code unless explicitly requested.

